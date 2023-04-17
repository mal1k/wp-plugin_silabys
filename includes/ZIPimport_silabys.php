<?php
function ZIPimport_silabys() {
?>

    <div class="wrap">
        <h2>
            <?php _e('Імпорт силабусів', 'myos') ?>
            <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=silabys');?>"><?php _e('Назад до силабусів', 'myos')?></a>
        </h2>
        <?php echo $message; ?>
        <div class="bootstrap-wrapper">
            <div class="col-md-6">
                <div class="">
                    <form id="silabys-form" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ZIPimport" value="<?php echo time(); ?>"/>
                        <div class="file-upload" style="margin-top: 24px;">
                            <div class="file-select">
                                <div class="file-select-button" id="fileName">Оберіть файл</div>
                                <div class="file-select-name" id="noFile">Оберіть файл</div> 
                                <input type="file" name="zip_file" id="file">
                                <button value="submit_post" type="submit" class="file-import-button" id="fileName">Імпортувати</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            if ( isset( $_POST['ZIPimport'] ) ) {
                $ignore_elements = [
                    '.DS_Store'
                ];
        
                $ignore_start_folders = [
                    '',
                    'silabys',
                    '__MACOSX'
                ];
        
                // Путь к загруженному zip-файлу
                $zip_file = $_FILES['zip_file']['tmp_name'];
        
                // Открываем zip-архив
                $zip = new ZipArchive;
                if ( $zip->open( $zip_file ) === true ) {        
                    $ignoredFolders = array();
                    $fileChanged    = array();
                    // Обходим все файлы и папки внутри архива
                    for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                        // Имя файла или папки
                        $name = $zip->getNameIndex( $i );

                        // Если элемент является файлом
                        if ( !substr( $name, -1 ) != '/' ) {
        
                            $directory = explode('/', dirname($name));
                            $year = $directory[1];
                            $direction = $directory[2];
                            $course = $directory[3];
        
                            $includedParts = [
                                'Б'  => 4,
                                'МП' => 2,
                                'МН' => 2,
                                'ДФ' => 2
                            ];
        

                            if ( in_array($year, $ignore_start_folders) ) {
                                continue;
                            }

                            $upload_dir = wp_upload_dir();
                            $dir = $upload_dir['basedir'] . '/' . 'silabys/';

                            if ( !preg_match('/[0-9]{4}[-][0-9]{4}/', $year) ) { // пошук року
                                $dir = $dir . $year;
                                $files = scandir($dir);

                                foreach ($files as $file) {
                                    if ($file !== '.' && $file !== '..' && !in_array($file, $ignore_elements)) { // пропускаємо посилання на поточну, батьківську папки та ігнор файли
                                        $path = $dir . '/' . $file;
                                        if (is_file($path)) { // перевіряємо, чи є елемент файлом
                                            $ignoredFolders[] = '<b>Рік</b>: ' . $year;
                                            break;
                                        }
                                    }
                                }

                                continue;
                            }
                            elseif ( !array_key_exists($direction, $includedParts) ) { // пошук напрямку
                                $dir = $dir . $year . '/' . $direction;
                                $files = scandir($dir);

                                foreach ($files as $file) {
                                    if ($file !== '.' && $file !== '..' && !in_array($file, $ignore_elements)) { // пропускаємо посилання на поточну, батьківську папки та ігнор файли
                                        $path = $dir . '/' . $file;
                                        if (is_file($path)) { // перевіряємо, чи є елемент файлом
                                            $directionName = !empty($direction) ? '/' . $direction : '/{файли}';
                                            $ignoredFolders[] = '<b>Напрямок</b>: ' . $year . $directionName;
                                            break;
                                        }
                                    }
                                }

                                continue;
                            }
                            elseif ( empty((int)$course) || $includedParts[$direction] < $course ) { // пошук курсу
                                $dir = $dir . $year . '/' . $direction . '/' . $course;
                                $files = scandir($dir);

                                foreach ($files as $file) {
                                    if ($file !== '.' && $file !== '..' && !in_array($file, $ignore_elements)) { // пропускаємо посилання на поточну, батьківську папки та ігнор файли
                                        $path = $dir . '/' . $file;
                                        if (is_file($path)) { // перевіряємо, чи є елемент файлом
                                            $courseName = !empty($course) ? '/' . $course : '/{файли}';
                                            $ignoredFolders[] = '<b>Курс</b>: ' . $year . '/' . $direction . $courseName;
                                            break;
                                        }
                                    }
                                }
                                continue;
                            }
        
                            $index = $zip->locateName($name, ZipArchive::FL_NOCASE); // шукаємо індекс файлу по дорозі
                            if ($index !== false) {
                                $upload_dir = wp_upload_dir();
                                $fileInfo = $zip->statIndex($index); // отримуємо інформацію про файл за індексом
                                $fileInfo['name'] = $fileInfo['name'];
        
                                $zip->extractTo($upload_dir['basedir'], $fileInfo['name']); // записуємо файл у uploads
                                
                                global $wpdb;
                                $table_name = $wpdb->prefix . 'silabys';
                                $fileName = pathinfo($fileInfo['name'], PATHINFO_FILENAME);
                                $item = [
                                    'title'         => $fileName,
                                    'academic_year' => $year,
                                    'direction'     => $direction,
                                    'course'        => $course,
                                    'file_path'     => '/'.$fileInfo['name'],
                                    'file_name'     => $fileName
                                ];

                                $silabys_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE 
                                    title = \"".addslashes($fileName)."\" AND
                                    academic_year = \"$year\" AND
                                    direction = \"$direction\" AND
                                    course = \"$course\""
                                ), ARRAY_A); // шукаємо у бд

                                if ( !empty($silabys_info) ) {
                                    $fileChanged[] = $fileInfo['name'];
                                    $silabys_id = $silabys_info['id'];
                                    $wpdb->update($table_name, $item, array('id' => $silabys_id));
                                }
                                else {
                                    $wpdb->insert($table_name, $item); // записуємо файл у бд
                                }

                            }
        
        
                        }
                    }
                    $ignoredFolders = array_unique($ignoredFolders);
                    rsort($ignoredFolders);
                    rsort($fileChanged);

                    ?>

                    <div class="row col">
                        <div class="col-md">
                            <?php if (!empty($fileChanged)): ?>
                                <p class="infoImportText">Замінені файли: </p>
                                <div class="infoImportMessage">
                                    <ol>
                                        <?php
                                        foreach ($fileChanged as $file) {
                                            echo '<li>' . $file . '</li>';
                                        }
                                        ?>
                                    </ol>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md">
                            <?php if (!empty($ignoredFolders)): ?>
                                <p class="infoImportText">Проігноровані папки: </p>
                                <div class="infoImportMessage">
                                    <ol>
                                        <?php
                                        foreach ($ignoredFolders as $folder) {
                                            echo '<li>' . $folder . '</li>';
                                        }
                                        ?>
                                    </ol>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php
                    // Закрываем zip-архив
                    $zip->close();
        
                } else {
                    // Если не удалось открыть zip-архив
                    echo 'Ошибка: не удалось открыть zip-архив';
                }
            }
            ?>
        </div>
    </div>

    <?php
 }