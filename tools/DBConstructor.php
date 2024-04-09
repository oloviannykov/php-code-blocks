<?php
/*
 * Helper for database creation during app installation process
 * Example:
        list($result, $error) = DBConstructor::create_db();
        if(! $result) {
            report_error(__METHOD__, "Failed to create tables: $error");
            return false; //database was not created
        }
        //now database is created and filled with initial data
 */

//todo: move this constants to configuration
define('DB_HOST', 'qwerty.com');
define('DB_USER', 'woody');
define('DB_PASS', 'KHmhg875(*&*432nbv');
define('DB_NAME', 'test_db');

//todo: implement these functions
function db_run_query($querySQL): bool
{
    return true;
}
function db_can_connect($error_reporting = true): bool
{
    return true;
}
function db_wrap_string($value): string
{
    return "'$value'";
}
function db_table_exists($table): bool
{
    return false;
}
function report_error(string $method, string $errorText): void
{
    echo "$method: $errorText";
}
//end of external functions

class DBConstructor
{
    public static function drop_table_if_exists($table_name)
    {
        if (empty($table_name)) {
            return false;
        }
        $query = 'DROP TABLE IF EXISTS ' . $table_name;
        return !empty(db_run_query($query));
    }

    public static function create_db()
    {
        if (
            !defined('DB_HOST') || !defined('DB_USER')
            || !defined('DB_PASS') || !defined('DB_NAME')
        ) {
            return [false, 'Missing connection parameters'];
        }
        if (!db_can_connect(false)) {
            return [false, 'Can not connect to data base'];
        }
        $settings = self::get_table_settings();
        foreach (self::get_tables_structure() as $table => $content) {
            self::drop_table_if_exists($table);
            if (empty($content)) {
                echo "skipping $table\n";
                continue;
            }
            $query = 'CREATE TABLE ' . $table . ' (' . $content . ') ' . $settings;
            if (!db_run_query($query)) {
                return [false, 'Failed to create ' . $table];
            }
        }
        foreach (self::get_tables_data() as $table => $records) {
            //INSERT INTO `bank_card_systems` VALUES (1,'Visa'),...,(9,'DinerClub');
            $records_list = [];
            foreach ($records as $record) {
                $values = [];
                foreach ($record as $value) {
                    $values[] = is_string($value) ? db_wrap_string($value) : (string) $value;
                }
                $records_list[] = '(' . implode(',', $values) . ')';
            }
            if (empty($records_list)) {
                echo "skipping data for $table\n";
                continue;
            }
            $query = 'INSERT INTO ' . $table . ' VALUES ' . implode(', ', $records_list);
            if (!db_run_query($query)) {
                return [false, 'Failed to fill ' . $table];
            }
        }
        return [true, ''];
    }

    public static function fill_table($table_name)
    {
        if (empty($table_name)) {
            return ['error' => 'wrong table name'];
        }
        $data = self::get_tables_data();
        if (empty($data) || !isset($data[$table_name])) {
            return ['error' => 'table records not found'];
        }
        //INSERT INTO `bank_card_systems` VALUES (1,'Visa'),...,(9,'DinerClub');
        $records_list = [];
        foreach ($data[$table_name] as $record) {
            $values = [];
            foreach ($record as $value) {
                $values[] = is_string($value) ? db_wrap_string($value) : (string) $value;
            }
            $records_list[] = '(' . implode(',', $values) . ')';
        }
        $query = 'INSERT INTO ' . $table_name . ' VALUES ' . implode(', ', $records_list);
        if (!db_run_query($query)) {
            return ['error' => 'query failed: ' . $query];
        }
        return ['added' => count($records_list)];
    }

    private static function get_table_settings()
    {
        return 'ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8';
    }

    private static function get_tables_structure()
    {
        return array(
            //examples:

            'table_name_1' => '`field1` varchar(256) NOT NULL, `field2` int(11) unsigned NOT NULL, `field3` varchar(20) NOT NULL',

            'INVENTORY_COUNTS' => '`id` int(11) unsigned NOT NULL AUTO_INCREMENT, '
                . '`ITEM_ID` int(11) unsigned NOT NULL, `EMPLOYEE_ID` int(11) unsigned NOT NULL, '
                . '`MOVEMENT_ITEM_ID` int(11) unsigned NOT NULL, `LOCATION_ID` int(11) unsigned NOT NULL, '
                . '`QUANTITY` decimal(7,2) DEFAULT \'0.00\', PRIMARY KEY (`id`)',
            /*
            'INVENTORY_ITEMS' => '`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `'.InventoryModel::ITEM_CATEGORY_ID.'` int(11) unsigned NOT NULL,
                `'.InventoryModel::ITEM_TITLE.'` text NOT NULL,
                `'.InventoryModel::ITEM_SIZE_OF_PIECE.'` decimal(7,2) NOT NULL,
                `'.InventoryModel::ITEM_SIZE_UNITS.'` varchar(10) NOT NULL,
                PRIMARY KEY (`id`)',
            */
        );
    }

    public static function create_table_if_not_exists($table_name, $table_structure = '')
    {
        if (empty($table_name)) {
            report_error(__METHOD__, 'empty table name: ' . $table_name);
            return false;
        }
        if (!db_table_exists($table_name)) {
            $query = self::construct_create_table_query($table_name, $table_structure);
            if (empty($query)) {
                report_error(__METHOD__, 'table structure not found: ' . $table_name);
                return false;
            }
            if (!db_run_query($query)) {
                report_error(__METHOD__, 'Failed to create: ' . $table_name);
                return false;
            }
        }
        return true;
    }

    public static function construct_create_table_query($table_name, $table_structure = '')
    {
        if (empty($table_name)) {
            return false;
        }
        if (empty($table_structure)) {
            $tables_structure = self::get_tables_structure();
            if (empty($tables_structure[$table_name])) {
                return false;
            }
            $table_structure = $tables_structure[$table_name];
        }
        return 'CREATE TABLE ' . $table_name . ' ('
            . $table_structure
            . ') ' . self::get_table_settings();
    }

    public static function get_tables_data()
    {
        return array(
            //examples:
            'BANKS' => [
                [1, 'ScotiaBank', '[1,2,3,4]'],
                [2, 'Banco Popular', '[1,2,3]'],
                [3, 'Banco de Reservas', '[1,2,3]'],
                [4, 'Banco Lopez de Haro', '[1,2,3]']
            ],
            'EMPLOYEE_OCCUPATIONS' => [
                [1, 'artisan'],
                [2, 'chauffeur'],
                [3, 'cooker'],
                [4, 'gardener'],
                [5, 'housemaid'],
                [6, 'majordomo'],
                [7, 'security'],
                [8, 'cleaner'],
                [9, 'electrician'],
                [10, 'waiter'],
            ],
        );
    }
}
