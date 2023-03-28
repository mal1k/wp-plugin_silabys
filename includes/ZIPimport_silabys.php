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

                            if ( !preg_match('/[0-9]{4}[-][0-9]{4}/', $year) ) { // пошук року
                                $ignoredFolders[] = $year;
                                continue;
                            }
                            elseif ( !array_key_exists($direction, $includedParts) ) { // пошук напрямку
                                $ignoredFolders[] = $year . '/' . $direction;
                                continue;
                            }
                            elseif ( empty((int)$course) || $includedParts[$direction] < $course ) { // пошук курсу
                                $ignoredFolders[] = $year . '/' . $direction . '/' . $course;
                                continue;
                            }
        
                            $index = $zip->locateName($name, ZipArchive::FL_NOCASE); // ищем индекс файла по пути
                            if ($index !== false) {
                                $upload_dir = wp_upload_dir();
                                $fileInfo = $zip->statIndex($index); // получаем информацию о файле по индексу
        
                                if (file_exists($upload_dir['basedir'] . '/' . $fileInfo['name']))
                                    $fileChanged[] = $fileInfo['name'];
        
                                $zip->extractTo($upload_dir['basedir'], $fileInfo['name']);
                            }
        
        
                        }
                    }
                    $ignoredFolders = array_unique($ignoredFolders);
                    rsort($ignoredFolders);
                    rsort($fileChanged);

                    ?>

                    <div class="row col">
                        <?php if (!empty($fileChanged)): ?>
                        <div class="col-md">
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
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($fileChanged)): ?>
                        <div class="col-md">
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
                        </div>
                        <?php endif; ?>
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