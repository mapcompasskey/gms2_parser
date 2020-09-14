<?php

/**
 * Game Maker Studio 2 Parser
 *
 */
class GMS2_Parser_2
{
    public $report = array();
    public $resources = array();
    
    public $navigation_scripts_array = array();
    public $navigation_scripts_content = null;
    
    public $navigation_rooms_array = array();
    public $navigation_rooms_content = null;
    
    private $_gms2_directory = 'C:/Users/MapCompassKey/MapCompassKey/Game Maker 2/';
    private $_project_directory = 'TestProject/';
    private $_base_directory = null;
    
    function __construct($project_directory = null)
    {
        $this->report['errors'] = array();
        $this->report['trace'] = array();
        
        // if a custom project directory was set
        if ($project_directory)
        {
            $this->_project_directory = $project_directory;
        }
        
        // set the base directory
        $this->_base_directory = $this->_gms2_directory . $this->_project_directory;
        $this->report['_base_directory'] = $this->_base_directory;
    }
    
    public function initialize()
    {
        $this->report['trace'][] = 'initialize()';
        
        // load the project file
        if ( ! $project_file_name = $this->_get_project_file_name())
        {
            return false;
        }
        
        // get the project file data
        if ( ! $project_file_data = $this->_get_project_file_data($project_file_name))
        {
            return false;
        }
        
        // get the project files resources array
        if ( ! $resources = $this->_get_project_file_resources($project_file_data))
        {
            return false;
        }
        
        // update the resources array
        if ( ! $this->_update_resources($resources))
        {
            return false;
        }
        
        // update 'scripts' navigation
        if ( ! $this->_get_navigation_scripts())
        {
            return false;
        }
        
        // update 'rooms' navigation
        if ( ! $this->_get_navigation_rooms())
        {
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Get Project File Name
     *
     * Load all the files in the project's root directory.
     * Iterate over the list and return the first file ending in "yyp".
     *
     * @return string
     */
    private function _get_project_file_name()
    {
        $this->report['trace'][] = '_get_project_file_name()';
        
        if ( ! $this->_base_directory)
        {
            $this->report['errors'][] = 'the base directory is not set';
            return false;
        }
        
        if ( ! is_dir($this->_base_directory))
        {
            $this->report['errors'][] = 'the base directory was not found';
            return false;
        }
        
        // get files in root project folder
        $files = scandir($this->_base_directory);
        
        // remove the current (.) and parent (..) directories
        $files = array_diff($files, array('..', '.'));
        
        // update the report
        $this->report['scandir'] = $files;
        
        // iterate through each file
        foreach ($files as $file)
        {
            if (strpos($file, '.yyp') !== false)
            {
                $this->report['project_yyp_file_name'] = $file;
                return $file;
            }
        }
        
        $this->report['errors'][] = 'the projects ".yyp" file was not found';
        return false;
    }
    
    
    /**
     * Get Project File Data
     *
     * Load the project file and convert its JSON string to an array.
     *
     * @return array
     */
    private function _get_project_file_data($file_name = '')
    {
        $this->report['trace'][] = array('_get_project_file_data($file_name)', $file_name);
        
        // get the project file
        $file = $this->_base_directory . $file_name;
        $this->report['project_yyp_file'] = $file;
        
        // if there was a problem loading and decoding the json file
        if ( ! $json = $this->_load_json_data($file))
        {
            return false;
        }
        
        return $json;
    }
    
    
    /**
     * Get the Project File's Resources
     *
     * Get the "resources" array from the project file's data array.
     *
     * @return array
     */
    private function _get_project_file_resources($data = array())
    {
        $this->report['trace'][] = array('_get_project_file_resources($data)', sprintf('%s object(s)', count($data, COUNT_RECURSIVE)));
        
        if ( ! $data['resources'])
        {
            $this->report['errors'][] = 'the project file data did not contain a "resources" key';
            return false;
        }
        
        if ( ! is_array($data['resources']))
        {
            $this->report['errors'][] = 'the project file data did not contain a "resources" array';
            return false;
        }
        
        return $data['resources'];
    }
    
    
    /**
     * Update Resources Data
     *
     * Iterate over the project file resources array looking for the following patterns.
     * Add all the matching resources to the resources array.
     *
     * Objects:
     *  id: {
     *      name: "obj_game",
     *      path: "objects/obj_game/obj_game.yy",
     *  order: 3,
     *
     * Scripts:
     *  id: {
     *      name: "src_game",
     *      path: "scripts/src_game/src_game.yy",
     *  order: 0,
     *
     * Rooms:
     *  id: {
     *      name: "rm_initialize",
     *      path: "rooms/rm_initialize/rm_initialize.yy",
     *  order: 0,
     *
     * @return bool
     */
    private function _update_resources($resources = array())
    {
        $this->report['trace'][] = array('_update_resources($data)', sprintf('%s object(s)', count($resources, COUNT_RECURSIVE)));
        
        foreach ($resources as $resource)
        {
            if ( ! isset($resource['id']))
            {
                continue;
            }
            
            if ( ! isset($resource['id']['name']))
            {
                continue;
            }
            
            if ( ! isset($resource['id']['path']))
            {
                continue;
            }
            
            if ( ! isset($resource['order']))
            {
                continue;
            }
            
            // get values
            $name = $resource['id']['name'];
            $path = $resource['id']['path'];
            $order = $resource['order'];
            
            // get the resource type from the path
            // example: scripts/src_game/src_game.yy
            $type = explode('/', $path);
            $type = array_shift($type);
            
            // if this is an allowed resource
            $allowed = array('scripts', 'rooms');
            if ( ! in_array($type, $allowed))
            {
                continue;
            }
            
            // create temporary array
            $arr = array();
            $arr['type'] = $type;
            $arr['name'] = $name;
            $arr['path'] = $path;
            $arr['order'] = $order;
            $arr['folder'] = '';
            $arr['gml'] = '';
            
            // get the folders this script is in
            if ($type == 'scripts')
            {
                if ($folder = $this->_get_script_folder($path))
                {
                    $arr['folder'] = $folder;
                }
                
                $arr['gml'] = str_replace('.yy', '.gml', $path);
            }
            
            if ($type == 'rooms')
            {
                $path_arr = explode('/', $path);
                $path_arr_end = count($path_arr) - 1;
                $path_arr[$path_arr_end] = 'RoomCreationCode.gml';
                $arr['gml'] = join('/', $path_arr);
            }
            
            // add to the resources array
            $this->resources[] = $arr;
        }
        
        if (empty($this->resources))
        {
            $this->report['errors'][] = 'no scripts or rooms were found.';
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Get the Script's Folder
     *
     * @return boolean
     */
    private function _get_script_folder($path = null)
    {
        if ( ! $path)
        {
            return false;
        }
        
        // get the path to the file
        $file = $this->_base_directory . $path;
        
        // if there was a problem getting the json data
        if ( ! $data = $this->_load_json_data($file))
        {
            return false;
        }
        
        if ( ! isset($data['parent']))
        {
            return false;
        }
        
        if ( ! isset($data['parent']['name']))
        {
            return false;
        }
        
        if ( ! isset($data['parent']['path']))
        {
            return false;
        }
        
        // example: folders/Scripts/Entities.yy
        $path = $data['parent']['path'];
        $path = str_replace('folders/', '', $path);
        $path = str_replace('.yy', '', $path);
        return $path;
    }
    
    
    /**
     * Get Script's Navigation
     *
     * @return boolean
     */
    private function _get_navigation_scripts()
    {
        $navigation = array();
        
        foreach ($this->resources as $resource)
        {
            if ( ! isset($resource['type']))
            {
                continue;
            }
            
            if ($resource['type'] != 'scripts')
            {
                continue;
            }
            
            if ( ! isset($resource['name']))
            {
                continue;
            }
            
            if ( ! isset($resource['order']))
            {
                continue;
            }
            
            if ( ! isset($resource['folder']))
            {
                continue;
            }
            
            $name = $resource['name'];
            $order = $resource['order'];
            $folder = explode('/', $resource['folder']);
            
            $target = array();
            $target[$order] = sprintf('<a href="#%s">%s</a>', $name, $name);
            $target = $this->_folder_tree_array($target, $folder, count($folder));
            
            $navigation = array_merge_recursive($target, $navigation);
        }
        
        // update scripts
        $this->navigation_scripts_array = $navigation;
        $this->navigation_scripts_content = $this->_folder_tree_content($navigation);
        
        return true;
    }
    
    
    /**
     * Get Room's Navigation
     *
     * @return boolean
     */
    private function _get_navigation_rooms()
    {
        $navigation = array();
        
        foreach ($this->resources as $resource)
        {
            if ( ! isset($resource['type']))
            {
                continue;
            }
            
            if ($resource['type'] != 'rooms')
            {
                continue;
            }
            
            if ( ! isset($resource['name']))
            {
                continue;
            }
            
            if ( ! isset($resource['order']))
            {
                continue;
            }
            
            if ( ! isset($resource['folder']))
            {
                continue;
            }
            
            $name = $resource['name'];
            $order = $resource['order'];
            $folder = explode('/', $resource['folder']);
            
            $target = array();
            $target[$order] = sprintf('<a href="#%s">%s</a>', $name, $name);
            $target = $this->_folder_tree_array($target, $folder, count($folder));
            
            $navigation = array_merge_recursive($target, $navigation);
        }
        
        // update scripts
        $this->navigation_rooms_array = $navigation;
        $this->navigation_rooms_content = $this->_folder_tree_content($navigation);
        
        return true;
    }
    
    
    /**
     * Folder Tree Array
     *
     * Starting from the inner-most array, build a multidimensional array outward.
     *
     * Exampe:
     *  $this->_folder_tree_array([0 => 'scr_game'], ['folders', 'Scripts', 'Controllers'], 2)
     *
     * [0 => 'scr_game']
     * ['Controllers' => [0 => link]]
     * ['Scripts' => ['Controllers' => [0 => link]]]
     * ['folders' => ['Scripts' => ['Controllers' => [0 => link]]]]
     *
     * @return array
     */
    private function _folder_tree_array($target = array(), $source = array(), $index = 0)
    {
        $index = $index - 1;
        
        if ($index < 0)
        {
            return $target;
        }
        
        if ( ! $key = $source[$index])
        {
            return $target;
        }
        
        $arr = array();
        $arr[$key] = $target;
        
        return $this->_folder_tree_array($arr, $source, $index);
    }
    
    
    /**
     * Folder Tree Content
     *
     * @return html
     */
    private function _folder_tree_content($value)
    {
        if (is_array($value))
        {
            $c = '<ul>';
            foreach($value as $key => $val)
            {
                if (is_array($val))
                {
                    $c .= '<li>';
                        $c .= sprintf('<span>%s</span>', $key);
                        $c .= $this->_folder_tree_content($val);
                    $c .= '</li>';
                }
                else
                {
                    $c .= $this->_folder_tree_content($val);
                }
            }
            $c .= '</ul>';
            return $c;
        }
        
        return sprintf('<li>%s</li>', $value);
    }
    
    
    /**
     * Get JSON Data
     *
     * Get the contents of a file and attempt to decode the string into a json array.
     * The files may contain "invalid" json strings with trailing commas at the end of objects and arrays.
     * PHP is unable to decode these strings so the extra commas need to be removed.
     */
    private function _load_json_data($file = null)
    {
        if ( ! $file)
        {
            return false;
        }
        
        if ( ! is_file($file))
        {
            $this->report['errors'][] = sprintf('file was not found: %s', $file);
            return false;
        }
        
        if ( ! $contents = file_get_contents($file))
        {
            $this->report['errors'][] = sprintf('could not get file contents: %s', $file);
            return false;
        }
        
        // remove the new lines
        $contents = str_replace("\n", '', $contents);
        $contents = str_replace("\r", '', $contents);
        $contents = str_replace("\r\n", '', $contents);
        
        // the json string has trailing commas at the end of objects and arrays
        // {one, two, three,} and [one, two three,]
        $contents = preg_replace('/,\s*}/', '}', $contents);
        $contents = preg_replace('/,\s*]/', ']', $contents);
        
        // if the string is unable to be decoded
        if ( ! $json = json_decode($contents, true))
        {
            $this->report['errors'][] = sprintf('unable to json decode the file contents: %s', $file);
            return false;
        }
        
        return $json;
    }
    
    
    /**
     * Output Text File
     *
     * Load a *.gml text file and output it directly to the browser.
     */
    public function output_text_file($resource = array())
    {
        $name = 'unknown';
        
        if ( ! is_array($resource))
        {
            $this->_output_text_file($name, 'resource is not an array');
            return;
        }
        
        if ( ! isset($resource['name']))
        {
            $this->_output_text_file($name, 'resource "name" was not set');
            return;
        }
        
        // name of the file
        $name = $resource['name'];
        
        if ( ! isset($resource['gml']))
        {
            $this->_output_text_file($name, 'resource "gml" was not set');
            return;
        }
        
        // path to the file
        $file = $this->_base_directory . $resource['gml'];
        
        // if file not found
        if ( ! is_file($file))
        {
            // if a room doesn't have any "Creation Code"
            if ($resource['type'] == 'rooms')
            {
                $this->_output_text_file($name, 'no creation code');
                return;
            }
            
            $content = sprintf('<pre>file was not found: %s</pre>', $file);
            $this->_output_text_file($name, $content);
            return;
        }
        
        // if the content could not be loaded
        if ( ! $contents = file_get_contents($file))
        {
            $content = sprintf('<pre>could not get file contents: %s<pre>', $file);
            $this->_output_text_file($name, $content);
            return;
        }
        
        // output html
        $this->_output_text_file($name, $contents);
        return;
    }
    
    private function _output_text_file($name, $content)
    {
        echo '<div class="code-block">';
            echo sprintf('<a class="anchor" id="%s" name="%s"></a>', $name, $name);
            echo sprintf('<header>%s</header>', $name);
            echo '<pre>';
                echo $content;
            echo '</pre>';
        echo '</div>';
    }
    
}

