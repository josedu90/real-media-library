<?php
namespace MatthiasWeb\RealMediaLibrary\general;
use MatthiasWeb\RealMediaLibrary\base;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Handles the view for dropdowns and custom UI's for folders.
 */
class View extends base\Base {
    private $structure;
    
    private $namesSlugArrayCache = null;
    
    public function __construct($structure) {
        $this->structure = $structure;
    }
    
    /**
     * @see wp_rml_selector()
     */
    public function selector($options) {
        $options = wp_parse_args($options, array(
            'selected' => _wp_rml_root(),
            'disabled' => array(),
            'nullable' => false,
            'editable' => true,
            'name' => false
        ));
        
        $name = empty($options['name']) ? '' : 'name="' . $options['name'] . '"';
        return '<input type="hidden" value="' . esc_attr($options['selected']) . '" ' . $name . '
            data-nullable="' . esc_attr(($options['nullable'] ? 'true' : 'false')) . '"
            data-editable="' . esc_attr(($options['editable'] ? 'true' : 'false')) . '"
            data-disabled="' . esc_attr(join(',', $options['disabled'])) . '"
            data-wprfc-visible="1" data-wprfc="preUploadUi" />';
    }
    
    /**
     * Create dropdown from the current users tree.
     * 
     * @returns string HTML
     */
    public function dropdown($selected, $disabled, $useAll = true) {
        return $this->optionsHTML($selected, null, "", "&nbsp;&nbsp;", $useAll, $disabled);
    }
    
    /**
     * Gets a HTML formatted string for <option>.
     */
    private function optionsHTML($selected = -1, $tree = null, $slashed = "", $spaces = "&nbsp;&nbsp;", $useAll = true, $disabled = null) {
        $return = '';
        $selected = $selected == -1 ? _wp_rml_root() : $selected;
        
        if ($disabled === null) {
            $disabled = array();
        }
        
        if ($tree == null) {
            $root = _wp_rml_root();
            $tree = $this->structure->getTree();
            if ($useAll) {
                $return .= '<option value="" ' . $this->optionsSelected($selected, "") . 
                                ((in_array(RML_TYPE_ALL, $disabled)) ? 'disabled="disabled"' : '') . 
                                '>' . __('All', RML_TD) . '</option>';
            }
            $return .= '<option value="' . $root . '" ' . $this->optionsSelected($selected, $root) . 'data-path="/"' .
                            ((in_array(RML_TYPE_ROOT, $disabled)) ? 'disabled="disabled"' : '') .
                            ' data-name="' . esc_attr(__('/ Unorganized', RML_TD)) . '"' .
                            ' data-type="' . RML_TYPE_ROOT . '">' . __('/ Unorganized', RML_TD) . '</option>';
        }
        
        if(!is_null($tree) && count($tree) > 0) {
            foreach($tree as $parent) {
                $return .= '<option value="' . $parent->getId() . '" ' . $this->optionsSelected($selected, $parent->getId()) .
                                    ' data-path="/' . esc_attr($parent->getAbsolutePath()) . '"' .
                                    ' data-name="' . esc_attr($parent->getName()) . '"' .
                                    ' data-type="' . $parent->getType() . '"' .
                                    ((in_array($parent->getType(), $disabled)) ? ' disabled="disabled" ' : '') . '>' .
                                    $spaces . '&nbsp;' . $parent->getName(true) . '</option>';
                
                if (is_array($parent->getChildren()) &&
                    count($parent->getChildren()) > 0
                    ) {
                    $return .= $this->optionsHTML($selected, $parent->getChildren(), $slashed, str_repeat($spaces, 2), $useAll, $disabled);
                }
            }
        }
        
        return $return;
    }
    
    public function optionsSelected($selected, $value) {
        if ((is_array($selected) && in_array($value, $selected)) || $selected == $value) {
            return 'selected="selected"';
        }else{
            return '';
        }
    }
    
    /*
     * Get array for the javascript backbone view.
     * The private namesSlugArray is for caching purposes
     * and can be resetted with the given function.
     */
    public function namesSlugArray($tree = null, $spaces = "--", $forceReload = false) {
        if ($forceReload || $this->namesSlugArrayCache == null) {
            $result = $this->namesSlugArrayRec($tree, $spaces);
        }else{
            $result = $this->namesSlugArrayCache;
        }
        $this->namesSlugArrayCache = $result;
        return $result;
    }
    
    private function namesSlugArrayRec($tree = null, $spaces = "--") {
        $return = array(
            "names" => array(),
            "slugs" => array(),
            "types" => array()
        );
        
        if ($tree == null) {
            $tree = $this->structure->getTree();
            $return["names"][] = __('Unorganized pictures', RML_TD);
            $return["slugs"][] = _wp_rml_root();
            $return["types"][] = 0;
        }
        
        if(!is_null($tree) && count($tree) > 0) {
            foreach($tree as $parent) {
                $return["names"][] = $spaces . ' ' . $parent->getName();
                $return["slugs"][] = $parent->getId();
                $return["types"][] = $parent->getType();
                
                if (is_array($parent->getChildren()) &&
                    count($parent->getChildren()) > 0
                    ) {
                    $append = $this->namesSlugArrayRec($parent->getChildren(), $spaces . "--");
                    $return["names"] = array_merge($return["names"], $append["names"]);
                    $return["slugs"] = array_merge($return["slugs"], $append["slugs"]);
                    $return["types"] = array_merge($return["types"], $append["types"]);
                }
            }
        }
        
        return $return;
    }
    
    public function getStructure() {
        return $this->structure;
    }
}

?>