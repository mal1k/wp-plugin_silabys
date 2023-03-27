<?php

/**
 * Plugin Name: Силабуси
 * Description: CRUD силабусів для SCS KPI UA.
 * Version: 1.1
 * Author: Котлярський Алекс
 * Author URI: https://t.me/alex_mal1k
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: myos
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) or die( '¡Sin trampas!' );

require plugin_dir_path( __FILE__ ) . 'includes/functions.php';

function myos_custom_admin_styles() {
    wp_enqueue_style('custom-styles', plugins_url('/assets/css/styles.css', __FILE__ ));
    wp_enqueue_style('custom-styles2', plugins_url('/assets/css/bootstrap.css', __FILE__ ));
}
add_action('admin_enqueue_scripts', 'myos_custom_admin_styles');

function load_custom_script() {
    wp_enqueue_script('custom_js_script', plugins_url('/assets/js/scripts.js', __FILE__), array('jquery'));
}
add_action( 'admin_enqueue_scripts', 'load_custom_script' );


function myos_plugin_load_textdomain() {
    load_plugin_textdomain( 'myos', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'myos_plugin_load_textdomain' );


global $myos_db_version;
$myos_db_version = '1.1.0'; 


function myos_install()
{
    global $wpdb;
    global $myos_db_version;

    $table_name = $wpdb->prefix . 'silabys'; 

    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      title varchar(255) NOT NULL, # назва
      academic_year varchar(9) NOT NULL, # рік
      file_path varchar(255) NOT NULL, # шлях до файлу
      file_name varchar(255) NOT NULL, # назва файлу
      direction ENUM('Б', 'МП', 'МН', 'ДФ') NOT NULL, # назва напряму підготовки
      course ENUM('1', '2', '3', '4') NOT NULL, # курс
      PRIMARY KEY  (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;";


    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('myos_db_version', $myos_db_version);

    $installed_ver = get_option('myos_db_version');
    if ($installed_ver != $myos_db_version) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('myos_db_version', $myos_db_version);
    }
}

register_activation_hook(__FILE__, 'myos_install');

// Видалити таблицю при вимкненні плагіна
function silabys_deactivation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'silabys';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_deactivation_hook( __FILE__, 'silabys_deactivation' );


function myos_install_data()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'silabys'; 

}

register_activation_hook(__FILE__, 'myos_install_data');


function myos_update_db_check()
{
    global $myos_db_version;
    if (get_site_option('myos_db_version') != $myos_db_version) {
        myos_install();
    }
}

add_action('plugins_loaded', 'myos_update_db_check');



if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Custom_Table_Silabys_List_Table extends WP_List_Table
 { 
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'single-silabys',
            'plural'   => 'sylabys',
        ));
    }


    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    function column_title($item)
    {

        $actions = array(
            'edit' => sprintf('<a href="?page=silabys_form&id=%s">%s</a>', $item['id'], __('Редагувати', 'silabys')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Видалити', 'silabys')),
        );

        return sprintf('%s %s',
            $item['title'],
            $this->row_actions($actions)
        );
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function column_file_name($item)
    {
        return '<a target="_blank" href="/wp-content/uploads'.$item['file_path'].'">' . $item['file_name'] . '</a>';
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', 
            'title' => __('Назва', 'silabys'),
            'academic_year' => __('Академічний рік', 'silabys'),
            'direction' => __('Напрямок', 'silabys'),
            'course' => __('Курс', 'silabys'),
            'file_name' => __('Назва файлу', 'silabys'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            // 'title' => __('title', true),
            // 'academic_year' => __('academic_year', true),
            // 'direction' => __('direction', true),
            // 'course' => __('course', true),
            // 'file_name' => __('file_name', true),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Видалити'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'silabys'; 

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'silabys'; 

        $per_page = 10; 

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
       
        $this->process_bulk_action();

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        $academic_year = $_REQUEST['academic_year'];
        $direction = $_REQUEST['direction'];
        $course = $_REQUEST['course'];
        $search = $_REQUEST['s'];

        if ( !empty($search) )
            $filters[] = "title = '$search' OR title = '$search' OR file_name = '$search' OR academic_year = '$search' OR direction = '$search' OR course = '$search'";

        if ( !empty($academic_year) )
            $filters[] = "academic_year = '$academic_year'";
        
        if ( !empty($direction) )
            $filters[] = "direction = '$direction'";
        
        if ( !empty($course) )
            $filters[] = "course = '$course'";

        if ( !empty($filters) )
            $filters = ' WHERE ' . implode(' AND ', $filters);

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        // if ( !empty($filters) )
        //     die($wpdb->prepare("SELECT * FROM $table_name $filters ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged));

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $filters ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);


        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
}

function myos_admin_menu()
{
    add_menu_page(__('Silabys', 'myos'), __('Силабуси', 'myos'), 'activate_plugins', 'silabys', 'myos_contacts_page_handler');
    add_submenu_page('silabys', __('Силабуси', 'myos'), __('Силабуси', 'myos'), 'activate_plugins', 'silabys', 'myos_contacts_page_handler');
   
    add_submenu_page('silabys', __('Додати силабус', 'myos'), __('Додати силабус', 'myos'), 'activate_plugins', 'silabys_form', 'myos_contacts_form_page_handler');
}

add_action('admin_menu', 'myos_admin_menu');


function myos_validate_contact($item)
{
    $messages = array();

    if (empty($item['title'])) $messages[] = __('Необхідно вказати назву', 'myos');
    if (empty($item['academic_year'])) $messages[] = __('Вкажіть навчальний рік', 'myos');
    if ( !preg_match('/[0-9]{4}[-][0-9]{4}/', $item['academic_year']) ) $messages[] = __('Необхідно вказати навчальний рік в форматі XXXX-XXXX');    
    if (empty($item['direction'])) $messages[] = __('Необхідно вказати напрямок');
    if ($item['direction'] != 'Б' && (int)$item['course'] > 2 || empty($item['course'])) $messages[] = __('Необхідно вказати курс');
    if (!(bool)$item['file_path']) $messages[] = __('Файл обовʼязковий');
    
    if (empty($messages)) return true;
    return implode('<br />', $messages);
}


function myos_languages()
{
    load_plugin_textdomain('myos', false, dirname(plugin_basename(__FILE__)));
}

add_action('init', 'myos_languages');