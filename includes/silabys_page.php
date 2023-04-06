<?php
// Add a shortcode for the custom page
function silabys_shortcode() {
    ob_start();
    ?>

<style>
@media (min-width: 768px) {
  .panel-heading {
    display: none;
  }
  .panel {
    border: none;
    box-shadow: none;
  }
  .panel-collapse {
    height: auto;
  }
  .panel-collapse.collapse {
    display: block;
  }
}
@media (max-width: 767px) {
  .tab-content .tab-pane {
    display: block;
  }
  .nav-tabs {
    display: none;
  }
  .panel-title a {
    display: block;
  }
  .panel {
    margin: 0;
    box-shadow: none;
    border-radius: 0;
    margin-top: -2px;
  }
  .tab-pane:first-child .panel {
    border-radius: 5px 5px 0 0;
  }
  .tab-pane:last-child .panel {
    border-radius: 0 0 5px 5px;
  }
}

.academic_year_header {
    /* margin-left: 25px; */
}

.direction_header {
    margin-left: 25px;
    margin-bottom: 10px;
}

.course_header {
    margin-left: 50px;
    margin-bottom: 0;
}

.files_header {
    margin-left: 85px;
}

.mr-3 {
    margin-right: 20px;
}

.filterButton {
    max-width: 150px;
    font-size: 10px;
}

.filterText {
    padding: 0 0 5px 0;
}

.filterBlock {
    margin-bottom: 20px;
}

ul.nav {
    list-style: none;
    padding: 0;
}
ul.nav li {
    display: inline-flex;
}
ul.nav li {
    text-decoration: none;
    color: darkslategray;
    padding: 10px;
    transition: all 0.3s ease-in-out;
    border-bottom: 1px solid transparent;
}
ul.nav li:hover {
    cursor: pointer;
    color: gray;
    background: lightgrey;
}

.nav .active {
    color: slateblue;
    border-bottom: 1px solid slateblue;
}
.nav .active:hover {
    border-color: transparent;
    background: slateblue;
    color: white;
}

pre {
    padding: 10px;
	font-size: 16px;
    background: transparent;
    border: none;
	line-height: 1.5;
    padding-bottom: 0;
    margin-bottom: 0;
}

</style>

<div class="container">

<?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'silabys';

    $row = $wpdb->get_results("SELECT * FROM $table_name ORDER BY `academic_year` DESC");
    $academic_years = array();
    
    foreach ($row as $element) {
        $academic_years[] = $element->academic_year;
    }

    $academic_years = array_unique($academic_years);

    $academic_year = 'WHERE `academic_year` = ';
    
    if ( !empty($_REQUEST['academic_year']) )
        $selected_year = $_REQUEST['academic_year'];
    else
        $selected_year = get_site_option('currentYear');

    $academic_year .= "'$selected_year'";

    $sql_elements = $wpdb->get_results("SELECT * FROM $table_name $academic_year ORDER BY `academic_year` DESC");
    foreach ($sql_elements as $sql_element) {
        switch ($sql_element->direction) {
            case 'Б':
                $direction = 'Освітньо-професійна програма бакалаврської підготовки';
                $elements_b[$direction][$sql_element->course][$sql_element->title] = $sql_element->file_path;
                break;
            case 'МП':
                $direction = 'Освітньо-професійна програма магістерської підготовки';
                $elements_mp[$direction][$sql_element->course][$sql_element->title] = $sql_element->file_path;
                break;
            case 'МН':
                $direction = 'Освітньо-наукова програма магістерської підготовки';
                $elements_mn[$direction][$sql_element->course][$sql_element->title] = $sql_element->file_path;
                break;
            case 'ДФ':
                $direction = 'Програма підготовки докторів філософії';
                $elements_df[$direction][$sql_element->course][$sql_element->title] = $sql_element->file_path;
                break;
            default:
                break;

            // $elements[$direction][$sql_element->course][$sql_element->title] = $sql_element->file_path;
        }
    }
?>

<ul id="nav-tab" class="nav" style="margin: 10px 0;">
    <?php
        foreach ($academic_years as $year) {
            $selected = ($selected_year == $year) ? 'active' : '';
            echo "<li value='$year' class='academicYearPicker $selected'>$year</li>";
        }
    ?>
</ul>

<script>
    var academicYear = $('.academicYearPicker');
    // Добавляем обработчик события клика на этот элемент
    $('.academicYearPicker').on('click', function() {
        // Отримуємо значення атрибуту "value" елемента <li>
        var classValue = $(this).attr('value');
        
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;// URL поточної сторінки

        // Створюємо елементи форми передачі параметрів
        var input1 = document.createElement('input');
        input1.type = 'hidden';
        input1.name = 'academic_year';
        input1.value = classValue;
        form.appendChild(input1);

        // Додаємо форму на сторінку та відправляємо її
        document.body.appendChild(form);
        form.submit();
    
    });
</script>

<?php
    $countAcademicYears = 0;
    $upload_dir = wp_upload_dir()['baseurl'];
    // rsort($elements);
    foreach ($elements_b as $elementKey => $item) {
        sort($item);
        echo "<p class='direction_header'><b>$elementKey</b></p>";
        foreach ($item as $courseKey => $course) {
            echo "<p class='course_header'><b>" . $courseKey+1 . " курс</b></p>";
            echo "<pre><ol class='files_header'>";
            foreach ($course as $itemKey => $itemValue) {
                echo '<li>';
                echo "<a download href='$upload_dir$itemValue'>$itemKey</a>";
                echo '</li>';
            }
            echo '</ol></pre>';
        }
    }
    // МП
    foreach ($elements_mp as $elementKey => $item) {
        sort($item);
        echo "<p class='direction_header'><b>$elementKey</b></p>";
        foreach ($item as $courseKey => $course) {
            echo "<p class='course_header'><b>" . $courseKey+1 . " курс</b></p>";
            echo "<pre><ol class='files_header'>";
            foreach ($course as $itemKey => $itemValue) {
                echo '<li>';
                echo "<a download href='$upload_dir$itemValue'>$itemKey</a>";
                echo '</li>';
            }
            echo '</ol></pre>';
        }
    }

    foreach ($elements_mn as $elementKey => $item) {
        sort($item);
        echo "<p class='direction_header'><b>$elementKey</b></p>";
        foreach ($item as $courseKey => $course) {
            echo "<p class='course_header'><b>" . $courseKey+1 . " курс</b></p>";
            echo "<pre><ol class='files_header'>";
            foreach ($course as $itemKey => $itemValue) {
                echo '<li>';
                echo "<a download href='$upload_dir$itemValue'>$itemKey</a>";
                echo '</li>';
            }
            echo '</ol></pre>';
        }
    }
    foreach ($elements_df as $elementKey => $item) {
        sort($item);
        echo "<p class='direction_header'><b>$elementKey</b></p>";
        foreach ($item as $courseKey => $course) {
            echo "<p class='course_header'><b>" . $courseKey+1 . " курс</b></p>";
            echo "<pre><ol class='files_header'>";
            foreach ($course as $itemKey => $itemValue) {
                echo '<li>';
                echo "<a download href='$upload_dir$itemValue'>$itemKey</a>";
                echo '</li>';
            }
            echo '</ol></pre>';
        }
    }
?>
</div>

<script>
$('#directionSelect').change(function(){
    if (this.value !== 'Б') {
      $('#course3filter').hide();
      $('#course4filter').hide();
    } else {
      $('#course3filter').show();
      $('#course4filter').show();
    }
});
</script>

<script>
    $('#currentYearSelect').change(function(){
        alert('submit');
    });
</script>

<?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode( 'silabys_table', 'silabys_shortcode' );