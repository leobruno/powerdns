<?php
/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <http://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2016  Poweradmin Development Team
 *      <http://www.poweradmin.org/credits.html>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Poweradmin installer
 *
 * @package     Poweradmin
 * @copyright   2007-2010 Rejo Zenger <rejo@zenger.nl>
 * @copyright   2010-2016 Poweradmin Development Team
 * @license     http://opensource.org/licenses/GPL-3.0 GPL
 */

// Dependencies
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/inc/error.inc.php';
require_once dirname(__DIR__) . '/inc/i18n.inc.php';

// Constants
define('LOCAL_CONFIG_FILE', dirname(__DIR__) . '/inc/config.inc.php');
define('SESSION_KEY_LENGTH', 46);

// Localize interface
$language = 'en_EN';
if (isset($_POST['language']) && $_POST['language'] != 'en_EN') {
    $language = $_POST['language'];

    $locale = setlocale(LC_ALL, $language, $language . '.UTF-8');
    if ($locale == false) {
        error(ERR_LOCALE_FAILURE);
    }

    $gettext_domain = 'messages';
    if (!function_exists('bindtextdomain')) {
        die(error('You have to install PHP gettext extension!'));
    }
    bindtextdomain($gettext_domain, "./../locale");
    textdomain($gettext_domain);
    @putenv('LANG=' . $language);
    @putenv('LANGUAGE=' . $language);
}

// Initialize Twig template engine
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
$twig->addExtension(new Twig_Extensions_Extension_I18n());

// Display header
$current_step = isset($_POST['step']) && is_numeric($_POST['step']) ? $_POST['step'] : 1;
echo $twig->render('header.html', array('current_step' => $current_step));

switch ($current_step) {

    case 1:
        echo $twig->render('step1.html', array('next_step' => ++$current_step));
        break;

    case 2:
        echo $twig->render('step2.html', array('next_step' => ++$current_step, 'language' => $language));
        break;

    case 3:
        echo $twig->render('step3.html', array('next_step' => ++$current_step, 'language' => $language));
        break;

    case 4:
        echo "<p>" . _('Updating database...') . " ";
        include_once("../inc/config-me.inc.php");
        $db_user = $_POST['user'];
        $db_pass = $_POST['pass'];
        $db_host = $_POST['host'];
        $db_port = $_POST['dbport'];
        $db_name = $_POST['name'];
        $db_charset = $_POST['charset'];
        $db_collation = $_POST['collation'];
        $db_type = $_POST['type'];
        if ($db_type == 'sqlite') {
            $db_file = $db_name;
        }
        $pa_pass = $_POST['pa_pass'];
        require_once("../inc/database.inc.php");
        $db_mdb2 = dbConnect();
        $db_mdb2->loadModule('Manager');
        $db_mdb2->loadModule('Extended');
        include_once("database-structure.inc.php");
        $current_tables = $db_mdb2->listTables();

        foreach ($def_tables as $table) {
            if (in_array($table['table_name'], $current_tables)) {
                $db_mdb2->dropTable($table['table_name']);
            }

            $options = $table['options'];

            if ($db_charset) {
                $options['charset'] = $db_charset;
            }

            if ($db_collation) {
                $options['collation'] = $db_collation;
            }
            $db_mdb2->createTable($table['table_name'], $table['fields'], $options);
        }

        $fill_perm_items = $db_mdb2->prepare('INSERT INTO perm_items VALUES (?, ?, ?)');
        $db_mdb2->extended->executeMultiple($fill_perm_items, $def_permissions);
        if (method_exists($fill_perm_items, 'free')) {
            $fill_perm_items->free();
        }
        foreach ($def_remaining_queries as $query_nr => $user_query) {
            if ($query_nr === 0) {
                $user_query = sprintf($user_query, $db_mdb2->quote(Poweradmin\Password::hash($pa_pass), 'text'));
            }
            $db_mdb2->query($user_query);
        }
        echo _('done!') . "</p>";

        echo $twig->render('step4.html', array(
            'next_step' => ++$current_step,
            'language' => $_POST['language'],
            'db_user' => $db_user,
            'db_pass' => $db_pass,
            'db_host' => $db_host,
            'db_port' => $db_port,
            'db_name' => $db_name,
            'db_type' => $db_type,
            'db_charset' => $db_charset,
            'pa_pass' => $pa_pass
        ));
        break;

    case 5:
        $current_step++;
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $db_host = $_POST['db_host'];
        $db_port = $_POST['db_port'];
        $db_name = $_POST['db_name'];
        $db_type = $_POST['db_type'];
        $db_charset = $_POST['db_charset'];
        if ($db_type == 'sqlite') {
            $db_file = $db_name;
        } else {
            $pa_db_user = $_POST['pa_db_user'];
            $pa_db_pass = $_POST['pa_db_pass'];
        }
        $pa_pass = $_POST['pa_pass'];
        $hostmaster = $_POST['dns_hostmaster'];
        $dns_ns1 = $_POST['dns_ns1'];
        $dns_ns2 = $_POST['dns_ns2'];

        require_once("../inc/database.inc.php");
        $db_mdb2 = dbConnect();
        include_once("database-structure.inc.php");

        echo "<p>" . _('You now want to give limited rights to Poweradmin so it can update the data in the tables. To do this, you should create a new user and give it rights to select, delete, insert and update records in the PowerDNS database.') . " ";
        if ($db_type == 'mysql') {
            $pa_db_host = $db_host;

            $sql = 'SELECT USER()';
            $result = $db_mdb2->queryRow($sql);
            if (isset($result['user()'])) {
                $current_db_user = $result['user()'];
                $pa_db_host = mb_substr($current_db_user, mb_strpos($current_db_user, '@') + 1);
            }

            echo _('In MySQL you should now perform the following command:') . "</p>";
            echo "<p><code>GRANT SELECT, INSERT, UPDATE, DELETE<BR>ON " . $db_name . ".*<br>TO '" . $pa_db_user . "'@'" . $pa_db_host . "'<br>IDENTIFIED BY '" . $pa_db_pass . "';</code></p>";
        } elseif ($db_type == 'pgsql') {
            echo _('On PgSQL you would use:') . "</p>";
            echo "<p><code>$ createuser -E -P " . $pa_db_user . "<br>" .
            "Enter password for new role: " . $pa_db_pass . "<br>" .
            "Enter it again: " . $pa_db_pass . "<br>" .
            "Shall the new role be a superuser? (y/n) n<br>" .
            "Shall the new user be allowed to create databases? (y/n) n<br>" .
            "Shall the new user be allowed to create more new users? (y/n) n<br>" .
            "CREATE USER<br>" .
            "$ psql " . $db_name . "<br>";
            echo "psql> ";
            foreach ($grantTables as $tableName) {
                echo "GRANT SELECT, INSERT, DELETE, UPDATE ON " . $tableName . " TO " . $pa_db_user . ";<br />";
            }
            foreach ($grantSequences as $sequenceName) {
                echo "GRANT USAGE, SELECT ON SEQUENCE " . $sequenceName . " TO " . $pa_db_user . ";<br />";
            }
            echo "</code></p>\n";
        }
        echo "<p>" . _('After you have added the new user, proceed with this installation procedure.') . "</p>\n";
        echo "<form method=\"post\">";
        echo "<input type=\"hidden\" name=\"db_host\" value=\"" . $db_host . "\">";
        echo "<input type=\"hidden\" name=\"db_name\" value=\"" . $db_name . "\">";
        echo "<input type=\"hidden\" name=\"db_port\" value=\"" . $db_port . "\">";
        echo "<input type=\"hidden\" name=\"db_type\" value=\"" . $db_type . "\">";
        echo "<input type=\"hidden\" name=\"db_user\" value=\"" . $db_user . "\">";
        echo "<input type=\"hidden\" name=\"db_pass\" value=\"" . $db_pass . "\">";
        echo "<input type=\"hidden\" name=\"db_charset\" value=\"" . $db_charset . "\">";
        if ($db_type != 'sqlite') {
            echo "<input type=\"hidden\" name=\"pa_db_user\" value=\"" . $pa_db_user . "\">";
            echo "<input type=\"hidden\" name=\"pa_db_pass\" value=\"" . $pa_db_pass . "\">";
        }
        echo "<input type=\"hidden\" name=\"pa_pass\" value=\"" . $pa_pass . "\">";
        echo "<input type=\"hidden\" name=\"dns_hostmaster\" value=\"" . $hostmaster . "\">";
        echo "<input type=\"hidden\" name=\"dns_ns1\" value=\"" . $dns_ns1 . "\">";
        echo "<input type=\"hidden\" name=\"dns_ns2\" value=\"" . $dns_ns2 . "\">";
        echo "<input type=\"hidden\" name=\"step\" value=\"" . $current_step . "\">";
        echo "<input type=\"hidden\" name=\"language\" value=\"" . $language . "\">";
        echo "<input type=\"submit\" name=\"submit\" value=\"" . _('Go to step') . " " . $current_step . "\">";
        echo "</form>";
        break;

    case 6:
        // Try to create configuration file
        $config_file_created = false;
        $configuration = ''; // FIXME
        if (is_writeable(LOCAL_CONFIG_FILE)) {
            $local_config = fopen(LOCAL_CONFIG_FILE, "w");
            fwrite($local_config, $configuration);
            fclose($local_config);
            $config_file_created = true;
        }

        // No need to set database port if it's standard port for that db
        $db_port = ($_POST['db_type'] == 'mysql' && $_POST['db_port'] != 3306)
            || ($_POST['db_type'] == 'pgsql' && $_POST['db_port'] != 5432) ? $_POST['db_port'] : '';

        // For SQLite we should provide path to db file
        $db_file = $_POST['db_type'] =='sqlite' ? $db_file = $_POST['db_name'] : '';

        echo $twig->render('step6.html', array(
            'next_step' => ++$current_step,
            'language' => $language,
            'config_file_created' => $config_file_created,
            'local_config_file' => LOCAL_CONFIG_FILE,
            'session_key' => Poweradmin\Password::salt(SESSION_KEY_LENGTH),
            'iface_lang' => $language,
            'dns_hostmaster' => $_POST['dns_hostmaster'],
            'dns_ns1' => $_POST['dns_ns1'],
            'dns_ns2' => $_POST['dns_ns2'],
            'db_host' => $_POST['db_host'],
            'db_user' => $_POST['pa_db_user'],
            'db_pass' => $_POST['pa_db_pass'],
            'db_name' => $_POST['db_name'],
            'db_type' => $_POST['db_type'],
            'db_port' => $db_port,
            'db_charset' => $_POST['db_charset'],
            'pa_pass' => $_POST['pa_pass']
        ));
        break;

    case 7:
        echo $twig->render('step7.html');
        break;

    default:
        break;
}

echo $twig->render('footer.html', array('version' => Poweradmin\Version::VERSION));