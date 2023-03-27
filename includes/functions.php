<?php
function myos_contacts_page_handler()
{
    global $wpdb;

    $table = new Custom_Table_Silabys_List_Table();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Видалено елементів: %d', 'myos'), count((array)$_REQUEST['id'])) . '</p></div>';
    }
    ?>
    <div class="wrap">

        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Силабуси', 'myos')?> <a class="add-new-h2"
                                    href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=silabys_form');?>"><?php _e('Додати силабус', 'myos')?></a>
        </h2>
        <?php echo $message; ?>

        <form id="contacts-table" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>

            <?php $table->search_box('Поиск', 'search_id'); ?>
            <?php
            $table_name = $wpdb->prefix . 'silabys'; 
            $row = $wpdb->get_results("SELECT * FROM $table_name");
            $academic_years = array();
            
            foreach ($row as $element)
                $academic_years[] = $element->academic_year;

            $academic_years = array_unique($academic_years);
            rsort($academic_years);

            // if ($table->total_items > 0):
            // print_r($table);
            if (!empty($table->_pagination_args['total_items'])): ?>
                <h4 class="mb-0">Фільтр:</h4>
                <select class="mr-3" name="academic_year" id="academic_year">
                    <option value="" <?php (empty($_REQUEST['academic_year'])) ? 'selected' : ''; ?>>Академічний рік</option>
                    <?php foreach ($academic_years as $year) {
                        $selected = ($_REQUEST['academic_year'] == $year) ? 'selected' : '';
                        echo "<option $selected value=$year>$year</option>";
                    } ?>
                </select>

                <select class="mr-3" name="direction" id="directionSelect">
                    <option value="" selected>Напрямок</option>
                    <?php
                        $directions = ['Б', 'МП', 'МН', 'ДФ'];
                        foreach ($directions as $direction) {
                            $selected = ($_REQUEST['direction'] == $direction) ? 'selected' : '';
                            echo "<option $selected value='$direction'>$direction</option>";
                        }
                    ?>
                </select>

                <select class="mr-3" name="course" id="course">
                    <option value="" selected>Курс</option>
                    <?php
                        $courses = [1, 2, 3, 4];
                        foreach ($courses as $course) {
                            $selected = ($_REQUEST['course'] == $course) ? 'selected' : '';
                            echo "<option id='course"   .$course."filter' $selected value='$course'>$course</option>";
                        }
                    ?>
                </select>

                <?php submit_button( 'Застосувати фільтри', 'button', false, false, array('id' => 'search-submit') ); 
            endif; ?>
            <?php $table->display() ?>
        </form>

    </div>
    <?php
}

function myos_contacts_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'silabys';
    $message = '';
    $notice = '';


    $default = array(
        'id'            => 0,
        'title'         => '',
        'academic_year' => '',
        'direction'     => '',
        'course'        => null,
        'file_path'     => '',
        'file_name'     => ''
    );


    // saving the form
    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        if ( isset( $_FILES['file'] ) && $_FILES['file']['error'] == UPLOAD_ERR_OK ) {
            $upload_dir = wp_upload_dir();
            $file_name = $_FILES['file']['name'];
            $file_tmp_name = $_FILES['file']['tmp_name'];
            $file_dest_name = $upload_dir['path'] . '/' . $file_name;
            move_uploaded_file( $file_tmp_name, $file_dest_name);
            $_REQUEST['file_path'] = $upload_dir['subdir'] . '/' . $file_name;

            if (empty($_REQUEST['file_name']))
                $_REQUEST['file_name'] = pathinfo($file_name, PATHINFO_FILENAME);
        }

        $item = shortcode_atts($default, $_REQUEST);

        $item_valid = myos_validate_contact($item);

        // print_r($item);

        if ($item_valid === true) {
            if ($item['id'] == 0) {
                $result = $wpdb->insert($table_name, $item);
                $item['id'] = $wpdb->insert_id;
                if ($result) {
                    $message = __('Елемент успішно збережено', 'myos');
                } else {
                    $notice = __('Під час збереження елемента сталася помилка', 'myos');
                }
            } else {
                try {
                    $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                } catch (\Throwable $th) {
                    // code
                }
                if ($result) {
                    $message = __('Елемент успішно оновлено', 'myos');
                } else {
                    $notice = __('Нічого не змінено', 'myos');
                }
                // $notice = __($result);
            }
        } else {
            $notice = $item_valid;
        }
    }
    else {
        
        $item = $default;
        if (isset($_REQUEST['id'])) {
            $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
            if (!$item) {
                $item = $default;
                $notice = __('Item not found', 'myos');
            }
        }
    }

    
    add_meta_box('contacts_form_meta_box', __('Силабус форма', 'myos'), 'myos_contacts_form_meta_box_handler', 'contact', 'normal', 'default');

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Додати силабус', 'myos')?> <a class="add-new-h2"
                                    href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=silabys');?>"><?php _e('Назад до силабусів', 'myos')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
        <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
        <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
            
            <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        
                        <?php do_meta_boxes('contact', 'normal', $item); ?>
                        <input type="submit" value="<?php _e('Зберегти', 'myos')?>" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function myos_contacts_form_meta_box_handler($item)
{
    ?>
    <tbody>
        <div class="formdatabc">		
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_file">
                <input id="file_path" name="file_path" type="hidden" value="<?php echo esc_attr($item['file_path'])?>">
                
                <div class="bootstrap-wrapper">
                    <div class="row">
                        <p class="col-md">			
                            <label class="required" for="title"><?php _e('Назва:', 'myos')?></label><br>
                            <input id="title" name="title" type="text" value="<?php echo esc_attr($item['title'])?>" required>
                        </p>
                        <p class="col-md">	
                            <label class="required" for="academic_year"><?php _e('Навчальний рік:', 'myos')?></label><br>
                            <input id="academic_year" name="academic_year" type="text" value="<?php echo esc_attr($item['academic_year'])?>" required>
                        </p>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <p class="mb-0">
                                <label class="required" for="direction"><?php _e('Напрямок:', 'myos')?></label>
                            </p>
                            <div class="row">
                                    <div class="col-md">
                                        <label>
                                            <input type="radio" name="direction" value="Б" <?php echo (empty($item['direction']) || $item['direction'] == 'Б') ? 'checked' : ''; ?>/>
                                            <span>Б</span>
                                        </label>
                                    </div>
                                    <div class="col-md">
                                        <label>
                                            <input type="radio" name="direction" value="МП" <?php echo ($item['direction'] == 'МП') ? 'checked' : ''; ?>/>
                                            <span>МП</span>
                                        </label>
                                    </div>
                                    <div class="col-md">
                                        <label>
                                            <input type="radio" name="direction" value="МН" <?php echo ($item['direction'] == 'МН') ? 'checked' : ''; ?>/>
                                            <span>МН</span>
                                        </label>
                                    </div>
                                    <div class="col-md">
                                        <label>
                                            <input type="radio" name="direction" value="ДФ" <?php echo ($item['direction'] == 'ДФ') ? 'checked' : ''; ?>/>
                                            <span>ДФ</span>
                                        </label>
                                    </div>
                            </div>
                        </div>
                        <div class="col-md">
                            <p class="mb-0">
                                <label class="required" for="course"><?php _e('Курс:', 'myos')?></label>
                            </p>
                            <div class="row">
                                    <div class="col-md">
                                        <label>
                                            <input type="radio" name="course" value="1" <?php echo (empty($item['course']) || $item['course'] == '1') ? 'checked' : ''; ?>/>
                                            <span>1</span>
                                        </label>
                                    </div>
                                    <div class="col-md">
                                        <label>
                                            <input type="radio" name="course" value="2" <?php echo ($item['course'] == '2') ? 'checked' : ''; ?>/>
                                            <span>2</span>
                                        </label>
                                    </div>
                                    <div class="col-md">
                                        <label id="course3">
                                            <input type="radio" name="course" value="3" <?php echo ($item['course'] == '3') ? 'checked' : ''; ?>/>
                                            <span>3</span>
                                        </label>
                                    </div>
                                    <div class="col-md">
                                        <label id="course4">
                                            <input type="radio" name="course" value="4" <?php echo ($item['course'] == '4') ? 'checked' : ''; ?>/>
                                            <span>4</span>
                                        </label>
                                    </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md">
                            <div class="file-upload" style="margin-top: 24px;">
                                <div class="file-select">
                                    <div class="file-select-button" id="fileName">Оберіть файл</div>
                                    <div class="file-select-name" id="noFile">
                                        <?php
                                            echo !empty($item['file_path']) ? end(explode('/', $item['file_path'])) : 'Оберіть файл';
                                        ?>
                                    </div> 
                                    <input type="file" name="file" id="file">
                                </div>
                            </div>
                        </div>
                        <div class="col-md">
                            <p>
                                <label for="file_name"><?php _e('Назва файлу:', 'myos')?></label>
                                <input id="file_name" name="file_name" type="text" value="<?php echo esc_attr($item['file_name'])?>">
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </tbody>
    <?php
}
?>