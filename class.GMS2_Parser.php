<?php

/**
 * Game Maker Studio 2 Parser
 *
 */
class GMS2_Parser
{
    public $report = array();
    public $resources = array();
    
    public $scripts_resource_folder = array();
    public $scripts_resource_tree = array();
    public $scripts_resource_files = array();
    
    public $rooms_resource_folder = array();
    public $rooms_resource_tree = array();
    public $rooms_resource_files = array();
    
    private $_gms2_directory = 'C:/Users/MapCompassKey/MapCompassKey/Game Maker 2/';
    private $_project_directory = 'Lost Wizard/';
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
        
        // get the views for folders in the resources array
        if ( ! $this->_update_resource_folders())
        {
            return false;
        }
        
        // get the "scripts" resources
        if ( ! $this->_get_scripts_resources())
        {
            return false;
        }
        
        // get the "rooms" resources
        if ( ! $this->_get_rooms_resources())
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
        
        if ( ! $json = json_decode($contents, true))
        {
            $this->report['errors'][] = 'unable to decode the file contents';
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
     * GMFolder
     *  Key: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX,
     *  Value: [
     *      id: YYYYYYYY-YYYY-YYYY-YYYY-YYYYYYYYYYYY,
     *      resourcePath: views\XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX.yy,
     *      resourceType: GMFolder
     *
     * GMScript
     *  Key: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX,
     *  Value: [
     *      id: YYYYYYYY-YYYY-YYYY-YYYY-YYYYYYYYYYYY,
     *      resourcePath: scripts\SCRIPT_NAME\SCRIPT_NAME.yy,
     *      resourceType: GMScript
     *
     * GMRoom
     *  Key: XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX,
     *  Value: [
     *      id: YYYYYYYY-YYYY-YYYY-YYYY-YYYYYYYYYYYY,
     *      resourcePath: rooms\ROOM_NAME\ROOM_NAME.yy,
     *      resourceType: GMRoom
     *
     * @return bool
     */
    private function _update_resources($resources = array())
    {
        $this->report['trace'][] = array('_update_resources($data)', sprintf('%s object(s)', count($resources, COUNT_RECURSIVE)));
        
        foreach ($resources as $resource)
        {
            if ( ! $key = $resource['Key'])
            {
                continue;
            }
            
            if ( ! $resource['Value'])
            {
                continue;
            }
            
            if ( ! $id = $resource['Value']['id'])
            {
                continue;
            }
            
            if ( ! $resource_path = $resource['Value']['resourcePath'])
            {
                continue;
            }
            
            if ( ! $resource_type = $resource['Value']['resourceType'])
            {
                continue;
            }
            
            if ($resource_type != 'GMFolder' && $resource_type != 'GMScript' && $resource_type != 'GMRoom')
            {
                continue;
            }
            
            // create a temporary array
            $arr = array();
            $arr['key'] = $key;
            $arr['id'] = $id;
            $arr['resource_path'] = $resource_path;
            $arr['resource_type'] = $resource_type;
            
            // add it to the resources array
            $this->resources[$key] = $arr;
        }
        
        // if no resources were found
        if (empty($this->resources))
        {
            $this->report['errors'][] = 'no folder, script, or room resources were found';
            return false;   
        }
        
        return true;
    }
    
    
    /**
     * Update Resource Folders
     *
     * Iterate over the resources array, looking for folder resource types, and then load their view data.
     *
     * @return bool
     */
    private function _update_resource_folders()
    {
        $this->report['trace'][] = '_update_resource_folders()';
        
        if (empty($this->resources))
        {
            $this->report['errors'][] = 'the resources array is empty';
            return false;
        }
        
        foreach ($this->resources as $key => $resource)
        {
            if ($resource['resource_type'] != 'GMFolder')
            {
                continue;
            }
            
            if ( ! isset($resource['resource_path']))
            {
                continue;
            }
            
            if (empty($resource['resource_path']))
            {
                continue;
            }
            
            $file = $this->_base_directory . $resource['resource_path'];
            if ( ! is_file($file))
            {
                $this->report['errors'][] = sprintf('file not found: %s', $file);
                return false;
            }
            
            if ( ! $file_contents = file_get_contents($file))
            {
                $this->report['errors'][] = sprintf('there was a problem reading the file contents: %s', $file);
                return false;
            }
            
            if ( ! $data = json_decode($file_contents, true))
            {
                $this->report['errors'][] = sprintf('there was a problem decoding the JSON string: %s', $file);
                return false;
            }
            
            // replace the resource in the array
            $this->resources[$key]['view_data'] = $data;
        }
        
        return true;
    }
    
    
    /**
     * Get the "Scripts" Resources
     *
     * @return boolean
     */
    private function _get_scripts_resources()
    {
        $this->report['trace'][] = '_get_scripts_resources()';
        
        // get the "scripts" resource folder
        if ( ! $scripts_resource_folder = $this->_get_scripts_resource_folder())
        {
            return false;
        }
        
        // update the scripts folder
        $this->scripts_resource_folder = $scripts_resource_folder;
        
        // get the resource tree array
        if ( ! $this->scripts_resource_tree = $this->_get_resource_tree($this->scripts_resource_folder))
        {
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Get the "ResourceTree_Scripts" Resource Folder
     *
     * Search through all the resources until finding the one with a "localisedFolderName" set to "ResourceTree_Scripts".
     *
     * @return array
     */
    private function _get_scripts_resource_folder()
    {
        $this->report['trace'][] = '_get_scripts_resource_folder()';
        
        if (empty($this->resources))
        {
            $this->report['errors'][] = 'the resources array is empty';
            return false;
        }
        
        foreach ($this->resources as $key => $resource)
        {
            if ( ! $resource['resource_type'])
            {
                continue;
            }
            
            if ($resource['resource_type'] != 'GMFolder')
            {
                continue;
            }
            
            if ( ! $resource['view_data'])
            {
                continue;
            }
            
            if ( ! $resource['view_data']['localisedFolderName'])
            {
                continue;
            }
            
            if ($resource['view_data']['localisedFolderName'] != 'ResourceTree_Scripts')
            {
                continue;
            }
            
            // capture the data
            $data = $resource;
            
            // remove the data from the resources array
            unset($this->resources[$key]);
            
            return $data;
        }
        
        return false;
    }
    
    
    /**
     * Get the "Rooms" Resources
     *
     * @return array
     */
    private function _get_rooms_resources()
    {
        $this->report['trace'][] = '_get_rooms_resources()';
        
        // get the "rooms" resource folder
        if ( ! $rooms_resource_folder = $this->_get_rooms_resource_folder())
        {
            return false;
        }
        
        // update the room folder
        $this->rooms_resource_folder = $rooms_resource_folder;
        
        // get the rooms resource tree array
        if ( ! $this->rooms_resource_tree = $this->_get_resource_tree($this->rooms_resource_folder))
        {
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Get the "ResourceTree_Rooms" Resource Folder
     *
     * Search through all the resources until finding the one with a "localisedFolderName" set to "ResourceTree_Rooms".
     *
     * @return array
     */
    private function _get_rooms_resource_folder()
    {
        $this->report['trace'][] = '_get_rooms_resource_folder()';
        
        if (empty($this->resources))
        {
            $this->report['errors'][] = 'the resources array is empty';
            return false;
        }
        
        foreach ($this->resources as $key => $resource)
        {
            if ( ! $resource['resource_type'])
            {
                continue;
            }
            
            if ($resource['resource_type'] != 'GMFolder')
            {
                continue;
            }
            
            if ( ! $resource['view_data'])
            {
                continue;
            }
            
            if ( ! $resource['view_data']['localisedFolderName'])
            {
                continue;
            }
            
            if ($resource['view_data']['localisedFolderName'] != 'ResourceTree_Rooms')
            {
                continue;
            }
            
            // capture the data
            $data = $resource;
            
            // remove the data from the resources array
            unset($this->resources[$key]);
            
            return $data;
        }
        
        return false;
    }
    
    
    /**
     * Get the Folder Tree
     *
     * Starting with the "scripts" or "rooms" resource folder, recursively pull children from the resourcse array.
     *
     * @return array
     */
    private function _get_resource_tree($resource_folder = array())
    {
        $this->report['trace'][] = '_get_resource_tree()';
        
        if ( ! $resource_folder)
        {
            $this->report['errors'][] = 'the resources folder is not set';
            return false;
        }
        
        if ( ! is_array($resource_folder))
        {
            $this->report['errors'][] = 'the resources folder is not an array';
            return false;
        }
        
        // build and return the resource tree
        $resource_tree = $this->_load_resource_children($resource_folder, 0);
        return $resource_tree;
    }
    
    
    /**
     * Load Resource Children
     *
     * @return array
     */
    private function _load_resource_children($data = array(), $depth = 0)
    {
        // $this->report['trace'][] = '_load_resource_children($data, $depth)';
        
        if ( ! $data['resource_type'])
        {
            $this->report['errors'][] = 'the data did not contain a "resource_type" key';
            return false;
        }
        
        // if this child is a GMFolder
        if ($data['resource_type'] == 'GMFolder')
        {
            return $this->_load_resource_child_folder($data, $depth);
        }
        
        // if this child is a GMScript
        else if ($data['resource_type'] == 'GMScript')
        {
            return $this->_load_resource_child_script($data, $depth);
        }
        
        // if this child is a GMRoom
        else if ($data['resource_type'] == 'GMRoom')
        {
            return $this->_load_resource_child_room($data, $depth);
        }
        
        return false;
    }
    
    
    /**
     * Load Resource Child Folder
     *
     * @return array
     */
    private function _load_resource_child_folder($data = array(), $depth = 0)
    {
        // $this->report['trace'][] = '_load_resource_child_folder($data, $depth)';
        
        if ( ! $data['view_data'])
        {
            $this->report['errors'][] = 'the data did not contain a "view_data" key';
            $this->report['errors'][] = $data;
            return false;
        }
        
        if ( ! $data['view_data']['folderName'])
        {
            $this->report['errors'][] = 'the data did not contain a "view_data => folderName" key';
            $this->report['errors'][] = $data;
            return false;
        }
        
        if ( ! $data['view_data']['children'])
        {
            $this->report['errors'][] = 'the data did not contain a "view_data => children" key';
            $this->report['errors'][] = $data;
            return false;
        }
        
        if ( ! is_array($data['view_data']['children']))
        {
            $this->report['errors'][] = 'the data did not contain a "view_data => children" array';
            $this->report['errors'][] = $data;
            return false;
        }
        
        if (empty($this->resources))
        {
            $this->report['errors'][] = 'the resources array is empty';
            return false;
        }
        
        $item = array();
        $item['name'] = $data['view_data']['folderName'];
        $item['type'] = 'folder';
        $item['children'] = array();
        
        // iterate over this resources children
        foreach ($data['view_data']['children'] as $key => $child)
        {
            if ( ! isset($this->resources[$child]))
            {
                continue;
            }
            
            if ( ! is_array($this->resources[$child]))
            {
                continue;
            }
            
            // capture the data
            $data_child = $this->resources[$child];
            
            // get and load any child resources
            $item['children'][$key] = $this->_load_resource_children($data_child, ($depth + 1));
        }
        
        return $item;
    }
    
    
    /**
     * Load Resource Child Script
     *
     * Take a file path to the script's JSON file and convert it to the script's GML text file.
     *  JSON: scripts\scr_player_create\scr_player_create.yy
     *  TEXT: scripts\scr_player_create\scr_player_create.gml
     *
     * @return array
     */
    private function _load_resource_child_script($data = array(), $depth = 0)
    {
        // $this->report['trace'][] = '_load_resource_child_script($data, $depth)';
        
        if ( ! $data['resource_path'])
        {
            return $data;
        }
        
        if (empty($data['resource_path']))
        {
            return $data;
        }
        
        // convert "DIR\DIR\FILE.yy" to "DIR\DIR\FILE.gml"
        $file_path = str_replace('.yy', '.gml', $data['resource_path']);
        
        // split the file path into parts and return the last part
        $paths = explode('\\', $file_path);
        $file_name = array_pop($paths);
        
        // remove ".gml" from the string
        $name = str_replace('.gml', '', $file_name);
        
        // add the script file to the array
        $this->scripts_resource_files[] = array($name, $file_path);
        
        $item = array();
        $item['name'] = $name;
        $item['type'] = 'script';
        
        return $item;
    }
    
    
    /**
     * Load Resource Child Room
     *
     * Take a file path to the script's JSON file and convert it to the room's Creation Code text file.
     *  JSON: rooms\rm_initialize\rm_initialize.yy
     *  TEXT: rooms\rm_initialize\RoomCreationCode.gml
     *
     * @return array
     */
    private function _load_resource_child_room($data = array(), $depth = 0)
    {
        // $this->report['trace'][] = '_load_resource_child_room($data, $depth)';
        
        if ( ! $data['resource_path'])
        {
            return $data;
        }
        
        if (empty($data['resource_path']))
        {
            return $data;
        }
        
        // convert "DIR\DIR\FILE.yy" to "DIR\DIR\FILE"
        $file_path = str_replace('.yy', '', $data['resource_path']);
        
        // split the file path into parts
        $paths = explode('\\', $file_path);
        
        // remove the last part from the array
        $name = array_pop($paths);
        
        // add the creation code file name and join the array back into a string
        array_push($paths, 'RoomCreationCode.gml');
        $file_path = join('\\', $paths);
        
        // add the room file to the array
        $this->rooms_resource_files[] = array($name, $file_path);
        
        $item = array();
        $item['name'] = $name;
        $item['type'] = 'room';
        
        return $item;
    }
    
    
    /**
     * Output Folder Tree
     *
     */
    public function output_resource_tree($node = array())
    {
        if ( ! isset($node['name']))
        {
            return;
        }
        
        if ( ! isset($node['type']))
        {
            return;
        }
        
        echo '<li>';
        
            // if this node is a "script"
            if ($node['type'] == 'script')
            {
                echo sprintf('<a href="#%s">%s</a>', $node['name'], $node['name']);
            }
            
            // if this node is a "room"
            else if ($node['type'] == 'room')
            {
                echo sprintf('<a href="#%s">%s</a>', $node['name'], $node['name']);
            }
            
            // else, this node is probably a "folder"
            else
            {
                echo sprintf('<span><strong>%s</strong></span>', $node['name']);
            }
            
            // if there are child nodes
            if (isset($node['children']))
            {
                if (is_array($node['children']))
                {
                    echo '<ul>';
                        foreach ($node['children'] as $child)
                        {
                            $this->output_resource_tree($child);
                        }
                    echo '</ul>';
                }
            }
        
        echo '</li>';
        
    }
    
    
    /**
     * Output Text File
     *
     * Load each text file and output it directly to the browser.
     */
    public function output_text_file($file = array())
    {
        if ( ! $file_title = $file[0])
        {
            echo sprintf('<pre>unable to get the file title</pre>');
            return;
        }
        
        if ( ! $file_path = $file[1])
        {
            echo sprintf('<pre>unable to get the file path</pre>');
            return;
        }
        
        $file = $this->_base_directory . $file_path;
        
        if ( ! is_file($file))
        {
            echo sprintf('file was not found: %s', $file);
            return;
        }
        
        if ( ! $contents = file_get_contents($file))
        {
            echo sprintf('could not get file contents: %s', $file);
            return;
        }
        
        echo '<div class="code-block">';
            echo sprintf('<a class="anchor" id="%s" name="%s"></a>', $file_title, $file_title);
            echo sprintf('<header>%s</header>', $file_title);
            echo '<pre>';
                echo $contents;
            echo '</pre>';
        echo '</div>';
        
    }
    
}

