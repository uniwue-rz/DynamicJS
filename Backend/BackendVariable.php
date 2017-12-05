<?php
/**
 * Backend Variable is used to define variables a backend needs. It is used to generate the interface for the configuration.
 *
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */
namespace Piwik\Plugins\DynamicJS\Backend;

class BackendVariable
{

    /**
     * Placeholder for the name
     *
     * @var string
     */
    private $name;

    /**
     * Placeholder for the type
     *
     * @var string
     */
    private $type;

    /**
     * Placeholder for the defaultValue.  When the type is radio or multiselect this should be an array.
     *
     * @var mix
     */
    private $defaultValue;

    /**
     * Placeholder for the DisplayName Key
     *
     * @var string
     */
    private $displayName;

    /**
     * Placeholder for the Inline Help
     *
     * @var string
     */
    private $inlineHelp;

    /**
     * Placeholder for the backend name
     *
     * @var string
     */
    private $backendName;

    /**
     * Constructor
     *
     * @param string $name              The name of the given variable
     * @param string $type              The type of the given variable
     * @param mix    $defaultValue      The default value of the given variable
     * @param string $displayName       The key to the display name, There should be a displayNameDesc also available.
     * @param string $inlineHelp        The inline help for the given variable
     * @param string $backedName        The name of the given backend
     */
    public function __construct($name, $type="text", $defaultValue = null, $displayName = null, $inlineHelp = null, $backendName = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->displayName = $displayName;
        $this->inlineHelp = $inlineHelp;
        $this->backendName = $backendName;
    }

    /**
     * Returns the name of the given variable
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of the given variable
     *
     * @param string $name The name of the given variable to be set
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the type of the given variable
     *
     * @return string
     */
    public function getType()
    {
        return $type;
    }

    /**
     * Sets the type of the given variable
     *
     * @return string
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Returns the default value
     *
     * @return mix
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Returns the name of the backend using the variable
     *
     * @return string
     */
    public function getBackendName()
    {
        return $this->backendName;
    }

    /**
     * Sets the backend name for the given system
     *
     * @return string
     */
    public function setBackendName($backendName)
    {
        $this->backendName = $backendName;
    }

    /**
     * Sets the default value for the given variable.
     *
     * @param mix $defaultValue The default value that should be used.
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Generates the piwik field for the given variable. It support text, radio, multiselect and checkbox.
     *
     * @return string
     */
    public function getPiwikField()
    {
        // All the fields have these
        $text = "<div piwik-field uicontrol=\"$this->type\" name=\"$this->name\" \n";
        $text = $text . "ng-model=\"adminController.data.$this->name\" \n";
        $text = $text . "title = \"{{'$this->displayName'|translate}}\" \n";
        $text = $text . "inline-help=\"{{'$this->inlineHelp'|translate}}\" \n";
        // Show the field only if the backend is active
        $text = $text . "ng-show =\"adminController.data.activeBackend == '$this->backendName'\" \n";
        // Field specific configuration
        switch ($this->type) {
            case 'text':
                $text = $text . "{% if DynamicJSConfig.$this->name is defined and DynamicJSConfig.$this->name != '' %} value=\"{{ DynamicJSConfig.$this->name }}\" {% endif %} >\n";
                $text = $text . "</div> \n";
                return $text;
                break;

            case 'password':
                $text = $text . "{% if DynamicJSConfig.$this->name is defined and DynamicJSConfig.$this->name != '' %} value=\"{{ DynamicJSConfig.$this->name }}\" {% endif %} >\n";
                $text = $text . "</div> \n";
                return $text;
                break;
            
            case 'checkbox':
                $text = $text . "{% if DynamicJSConfig.$this->name is defined and DynamicJSConfig.$this->name %} value=\"{{ DynamicJSConfig.$this->name }}\" {% endif %}> \n";
                $text = $text . "</div> \n";
                return $text;

            case 'multiselcet':
                $text = $text . "{% if DynamicJSConfig.$this->name is defined %} value=\"{{ DynamicJSConfig.$this->name | json_encode() | raw }}\" {% endif %} \n";
                $text = $text . "options= '". \json_encode($this->defaultValue)."'>";
                $text = $text . "</div> \n";
                return $text;

            case 'radio':
                $text = $text . "{% if DynamicJSConfig.$this->name is defined and DynamicJSConfig.$this->name != '' %} value=\"{{ DynamicJSConfig.$this->name }}\" {% endif %} \n";
                $text = $text . "options= '". \json_encode($this->defaultValue)."'>";
                $text = $text . "</div> \n";
                return $text;

            default:
                throw new \Exception("$this->type is not supported, please use text, radio, multiselect or checkbox as type");
                break;
        }
    }
}
