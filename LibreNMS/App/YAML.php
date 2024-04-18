<?php
/**
 * YAML.php
 *
 * Provides the basic app config fuctionality for YAML apps.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2024
 * @author     Zane C. Bowers-Hadley <vvelox@vvelox.net>
 */

namespace LibreNMS\App;

use LibreNMS\Exceptions\YamlAppAlreadyLoadedException;
use LibreNMS\Exceptions\YamlAppInvalidNameException;
use LibreNMS\Exceptions\YamlAppYamlDoesNotExistException;
use LibreNMS\Exceptions\YamlAppYamlLoadFailedException;
use LibreNMS\Exceptions\YamlAppGraphDoesNotExistException;

class YAML
{
    private static $app_config = [];
    private static $already_loaded = false;

    /**
     * Load the YAML for a app
     *
     * @param  string  $app
     * return &array
     **/
    public static function load($app)
    {
        // This method should only be called once upon initing the object.
        if (self::$already_loaded) {
            throw new YamlAppAlreadyLoadedException;
        }

        // Ensure that have a valid app name
        if (!preg_match('/^[A-Za-z0-9\-\_]+$/', $app)) {
            throw new YamlAppInvalidNameException;
        }

        $yaml_app_file = 'misc/yaml_apps/' . $app . '.yaml';

        // ensure that the specified yaml file exists
        if (!file_exists($yaml_app_file)) {
            throw new YamlAppYamlDoesNotExistException;
        }

        self::$app_config = yaml_parse_file($yaml_app_file, 0);

        if (self::$app_config == false) {
            throw new YamlAppYamlLoadFailedException;
        }

        self::$already_loaded = true;

        return self;
    }

    /**
     * Returns the config hash.
     *
     * return &array
     **/
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Load the YAML for a app
     *
     * @param  string  $graph_name
     * return &array
     **/
    public static function graphInfo($graph_name)
    {
        if (!isset($config['graphs']) ||
            !is_array($config['graphs']) ||
            !isset($config['graphs'][$graph_name]) ||
            !is_array($config['graphs'][$graph_name])
        ) {
            throw new YamlAppGraphDoesNotExistException;
        }
        return $config['graphs'][$graph_name];
    }

    /**
     * Top text fields to show for all pages.
     *
     * return &array
     **/
    public static function topTextFields()
    {
        if (!isset($config['top_text_fields']) ||
            !is_array($config['top_text_fields'])
        ) {
            return [];
        }
        return $config['top_text_fields'];
    }

    /**
     * Top text fields to show for the general page.
     *
     * return &array
     **/
    public static function topTextFieldsGeneral()
    {
        if (!isset($config['top_text_fields_general']) ||
            !is_array($config['top_text_fields_general'])
        ) {
            return [];
        }
        return $config['top_text_fields_general'];
    }
}
