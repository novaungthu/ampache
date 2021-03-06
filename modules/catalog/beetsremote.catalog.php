<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Beets Catalog Class
 *
 * This class handles all actual work in regards to remote Beets catalogs.
 *
 */
class Catalog_beetsremote extends Beets\Catalog
{
    protected $version = '000001';
    protected $type = 'beetsremote';
    protected $description = 'Beets Remote Catalog';

    protected $listCommand = 'item/query';

    /**
     *
     * @var string Beets Database File
     */
    protected $uri;

    /**
     * get_create_help
     * This returns hints on catalog creation
     */
    public function get_create_help()
    {
        $help = "<ul>" .
                "<li>Install Beets web plugin: http://beets.readthedocs.org/en/latest/plugins/web.html</li>" .
                "<li>Start Beets web server</li>" .
                "<li>Specify URI including port (like http://localhost:8337). It will be shown when starting Beets web in console.</li></ul>";
        return $help;
    }

    /**
     * is_installed
     * This returns true or false if remote catalog is installed
     */
    public function is_installed()
    {
        $sql = "SHOW TABLES LIKE 'catalog_beetsremote'";
        $db_results = Dba::query($sql);

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * install
     * This function installs the remote catalog
     */
    public function install()
    {
        $sql = "CREATE TABLE `catalog_beetsremote` (`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                "`uri` VARCHAR( 255 ) COLLATE utf8_unicode_ci NOT NULL , " .
                "`catalog_id` INT( 11 ) NOT NULL" .
                ") ENGINE = MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        Dba::query($sql);

        return true;
    }

    public function catalog_fields()
    {
        $fields['uri'] = array('description' => T_('Beets Server URI'), 'type' => 'textbox');

        return $fields;
    }

    /**
     * create_type
     *
     * This creates a new catalog type entry for a catalog
     * It checks to make sure its parameters is not already used before creating
     * the catalog.
     */
    public static function create_type($catalog_id, $data) { // TODO: This Method should be required / provided by parent
        $uri = $data['uri'];

        if (substr($uri, 0, 7) != 'http://' && substr($uri, 0, 8) != 'https://') {
            Error::add('general', T_('Error: Beets selected, but path is not a URL'));
            return false;
        }

        // Make sure this uri isn't already in use by an existing catalog
        $selectSql = 'SELECT `id` FROM `catalog_beets` WHERE `uri` = ?';
        $db_results = Dba::read($selectSql, array($uri));

        if (Dba::num_rows($db_results)) {
            debug_event('catalog', 'Cannot add catalog with duplicate uri ' . $uri, 1);
            Error::add('general', sprintf(T_('Error: Catalog with %s already exists'), $uri));
            return false;
        }

        $insertSql = 'INSERT INTO `catalog_beetsremote` (`uri`, `catalog_id`) VALUES (?, ?)';
        Dba::write($insertSql, array($uri, $catalog_id));
        return true;
    }

    protected function getParser()
    {
        return new Beets\JsonHandler($this->uri);
    }

    /**
     * Check if a song was added before
     * @param array $song
     * @return boolean
     */
    public function checkSong($song)
    {
        if ($song['added'] < $this->last_add) {
            debug_event('Check', 'Skipping ' . $song['file'] . ' File modify time before last add run', '3');
            return true;
        }

        return (boolean) $this->getIdFromPath($song['file']);
    }

}
