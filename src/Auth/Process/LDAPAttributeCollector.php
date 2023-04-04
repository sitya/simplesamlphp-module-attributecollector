<?php

declare(strict_types=1);

namespace SimpleSAML\Module\attributecollector\Auth\Process;

use Exception;
use SimpleSAML\Module\attributecollector\Collector\LDAPCollector;

class LDAPAttributeCollector extends \SimpleSAML\Auth\ProcessingFilter
{
    private $existing = 'ignore';
    private $collector = null;
    private $uidfield = null;

    /**
     * Initialize this filter.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!array_key_exists("uidfield", $config)) {
            throw new Exception('No uidfield specified in configuration');
        }
        $this->uidfield = $config["uidfield"];
        $this->collector = new LDAPCollector($config['collector']);
        if (array_key_exists("existing", $config)) {
            $this->existing = $config["existing"];
        }
    }


    /**
     * Apply filter expand attributes with collected ones
     *
     * @param array &$request  The current request
     */
    public function process(array &$request): void
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        if (array_key_exists($this->uidfield, $request['Attributes'])) {
            $newAttributes = $this->collector->getAttributes($request['Attributes'], $this->uidfield);

            if (is_array($newAttributes)) {
                $attributes =& $request['Attributes'];

                foreach ($newAttributes as $name => $values) {
                    if (!is_array($values)) {
                        $values = array($values);
                    }
                    if (!array_key_exists($name, $attributes) || $this->existing === 'replace') {
                        $attributes[$name] = $values;
                    } else {
                        if ($this->existing === 'merge') {
                            $attributes[$name] = array_merge($attributes[$name], $values);
                        }
                    }
                }
            }
        }
    }
}
