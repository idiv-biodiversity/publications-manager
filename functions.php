<?php
// Delete entry
add_action('wp_ajax_delete_entry', 'delete_entry_callback');
function delete_entry_callback() {
    global $wpdb;

    if (isset($_POST['id'])) {
        $id = $_POST['id'];
        
        $table = $wpdb->prefix . 'idiv_publications';
        $result = $wpdb->delete($table, array('id' => $id));

        // Check if deletion was successful
        if ($result !== false) {
            // Send success response
            wp_send_json_success('Publication deleted successfully');
        } else {
            // Send error response
            wp_send_json_error('Failed to delete publication');
        }
    } else {
        // Send error response for invalid request
        wp_send_json_error('Invalid request');
    }
}

function wrapper_start(){
    echo '<div id="pub_container">';
}

function wrapper_end(){
    echo '</div>';
}

// Upload
function publications_upload() { ?>
    <div class="row mb-5">
        <div class="col-md-6">
            <h3>Publications Manager</h3>
            <form method="post" enctype="multipart/form-data" id="xml-upload-form">
                <?php wp_nonce_field('xml_upload_nonce', 'xml_upload_nonce'); ?>
                <input type="file" name="xml_file" id="xml-file">
                <input type="submit" name="submit" class="btn btn-primary" value="Upload XML">
            </form>
        </div>
        <div class="col-md-6 text-right">
            <img src="<?php echo plugin_dir_path(__FILE__) . 'img/logo.png'; ?>" alt="iDiv logo" class="img-fluid">
        </div>
    </div>

    <?php
    if (isset($_POST['xml_upload_nonce']) && wp_verify_nonce($_POST['xml_upload_nonce'], 'xml_upload_nonce')) {
        if (isset($_FILES['xml_file']) && $_FILES['xml_file']['error'] == UPLOAD_ERR_OK && $_FILES['xml_file']['type'] == 'text/xml') {
            $xml_data = simplexml_load_file($_FILES['xml_file']['tmp_name']);

            // Debug output to check XML data
            // echo '<pre>';
            // print_r($xml_data);
            // echo '</pre>';

            foreach ($xml_data->records->record as $record) {

                // Authors
                $authors = array();
                if (isset($record->contributors->authors)) {
                    // Loop through each 'author' node
                    foreach ($record->contributors->authors->author as $author) {
                        // Extract the author's name from the 'style' tag and trim any whitespace
                        $author_name = trim(strval($author->style));

                        // Check if the author's style is bold
                        if (isset($author->style['face']) && strval($author->style['face']) === 'bold') {
                            // Wrap the author's name in <strong> tags
                            $author_name = '<strong>' . $author_name . '</strong>';
                        }

                        // Add the author's name to the array
                        $authors[] = str_replace(' ', '', $author_name);
                    }
                }
                $authors_string = implode(', ', $authors); // Concatenate the authors' names

                // Editors
                $editors = array();
                if (isset($record->contributors->{"secondary-authors"})) {
                    // Loop through each 'author' node
                    foreach ($record->contributors->{"secondary-authors"}->author as $author) {
                        // Extract the author's name from the 'style' tag and trim any whitespace
                        $author_name = trim(strval($author->style));
                        // Add the author's name to the array
                        $editors[] = str_replace(' ', '', $author_name);
                    }
                }
                $editors_string = implode(', ', $editors); // Concatenate the authors' names

                // title
                if (isset($record->titles->title)){
                    // Replace multiple consecutive whitespaces with a single whitespace
                    $title = preg_replace('/\s+/', ' ', $record->titles->title->style);
                }

                // book title
                if (isset($record->titles->{"secondary-title"})){
                    // Replace multiple consecutive whitespaces with a single whitespace
                    $book_title = preg_replace('/\s+/', ' ', $record->titles->{"secondary-title"}->style);
                }

                // Reference types
                if ( isset($record->{"ref-type"}['name']) ){
                    $ref_type = trim(strval($record->{"ref-type"}['name']));
                    switch ( $ref_type ) {
                        case 'Journal Article':
                          $ref_type = 'Journal';
                          break;
                        case 'Book Section':
                            $ref_type = 'Book Section';
                          break;
                        case 'Book':
                            $ref_type = 'Book/Report';
                          break;
                        case 'Report':
                            $ref_type = 'Book/Report';
                          break;
                      }
                }

                $data = array(
                    'rec_id' => isset($record->{"rec-number"}) ? trim(intval($record->{"rec-number"})) : NULL,
                    'ref_type' => isset($ref_type) ? $ref_type : '',
                    'title' => isset($title) ? trim(strval($title)) : '',
                    'authors' => isset($authors_string) ? $authors_string : '',
                    'journal' => isset($record->periodical->{"full-title"}->style) ? trim(strval($record->periodical->{"full-title"}->style)) : '',
                    'book_title' => isset($book_title) ? trim(strval($book_title)) : '',
                    'editors' => isset($editors_string) ? $editors_string : '',
                    'publisher' => isset($record->publisher->style) ? trim(strval($record->publisher->style)) : '',
                    'year_published' => isset($record->dates->year->style) ? intval($record->dates->year->style) : NULL,
                    'doi_link' => isset($record->{'electronic-resource-num'}->style) ? trim(strval($record->{'electronic-resource-num'}->style)) : ''
                );
                publications_insert($data);
            }
            echo '<div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Data successfully uploaded and saved to the database.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>';
        } else {
            echo '<div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Error loading XML file.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>';
        }
    }
}

// Insert into database
function publications_insert($data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'idiv_publications';
    $wpdb->insert($table_name, $data);
}

// Display Table of publications
function publications_table() {
    global $wpdb;

    // Get counts for each reference type
    $counts = $wpdb->get_results("SELECT ref_type, COUNT(*) AS count FROM {$wpdb->prefix}idiv_publications GROUP BY ref_type", ARRAY_A);
    // Get the total count of all reference types
    $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}idiv_publications");

    // Get distinct years
    $years = $wpdb->get_col("SELECT DISTINCT year_published FROM {$wpdb->prefix}idiv_publications ORDER BY year_published DESC");

    // Ensure the WP_List_Table class is loaded
    if (!class_exists('WP_List_Table')) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
    }

    class Publications_List_Table extends WP_List_Table {
        // Set up columns
        function get_columns() {
            $columns = array(
                'id' => 'ID',
                'rec_id' => 'Rec ID',
                'title' => 'Title',
                'ref_type' => 'Reference Type',
                'year_published' => 'Year of publication',
                'created_at' => 'Created at',
                'last_updated' => 'Last Update',
                'pub_status' => 'Status'
            );
            return $columns;
        }

        // Define sortable columns
        function get_sortable_columns() {
            $sortable_columns = array(
                'id' => array('id', true), // true: ascending, false: descending or unordered
                'rec_id' => array('rec_id', false),   
                'title' => array('title', false),     
                'ref_type' => array('ref_type', false),
                'year_published' => array('year_published', false),  
                'created_at' => array('created_at', false),  
                'last_updated' => array('last_updated', false),  
                'pub_status' => array('pub_status', false)
            );
            return $sortable_columns;
        }

        // Sort the items
        function usort_reorder($a, $b) {
            // If no sort, default to title
            $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id';
            // If no order, default to asc
            $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
            // Compare values based on orderby
            if ($orderby === 'title') {
                $result = strcmp($a->title, $b->title);
            } elseif ($orderby === 'id') {
                $result = $a->id - $b->id;
            } elseif ($orderby === 'rec_id') {
                $result = $a->rec_id - $b->rec_id;
            } elseif ($orderby === 'ref_type') {
                $result = strcmp($a->ref_type, $b->ref_type);
            } elseif ($orderby === 'year_published') {
                $result = strcmp($a->year_published, $b->year_published);
            } elseif ($orderby === 'created_at') {
                $result = strcmp($a->created_at, $b->created_at);
            } elseif ($orderby === 'last_updated') {
                $result = strcmp($a->last_updated, $b->last_updated);
            } elseif ($orderby === 'pub_status') {
                $result = strcmp($a->pub_status, $b->pub_status);
            } else {
                // Default sorting behavior
                $result = strcmp($a->id, $b->id);
            }
            // Adjust result based on order
            return ($order === 'asc') ? $result : -$result;
        }

        
        // Prepare items for display
        function prepare_items() {
            global $wpdb;

            // Define columns to search through
            $searchable_columns = array('id', 'rec_id', 'ref_type', 'title', 'authors', 'journal', 'editors', 'publisher', 'year_published', 'doi_link', 'pdf_link', 'data_link', 'code_link', 'created_at', 'last_updated', 'pub_status');

            // Query to retrieve publications
            $query = "SELECT * FROM {$wpdb->prefix}idiv_publications WHERE 1=1";

            // Handle filter clearing
            $clear_filters = isset($_GET['clear_filters']) ? sanitize_text_field($_GET['clear_filters']) : '';
            if ($clear_filters === '1') {
                // Unset the filter parameters
                if (isset($_GET['ref_type'])) unset($_GET['ref_type']);
                if (isset($_GET['year_published'])) unset($_GET['year_published']);
                if (isset($_GET['s'])) unset($_GET['s']);
                // Unset the "clear_filters" parameter
                unset($_GET['clear_filters']);
            }

            // Handle filtering
            $ref_type = isset($_GET['ref_type']) ? sanitize_text_field($_GET['ref_type']) : '';
            if (!empty($ref_type)) {
                $query .= " AND ref_type = '{$ref_type}'";
            }
            $year_published = isset($_GET['year_published']) ? intval($_GET['year_published']) : '';
            if (!empty($year_published)) {
                $query .= " AND year_published = '{$year_published}'";
            }

            // Handle searching 
            $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
            if (!empty($search_term)) {
                $query .= " AND (";
                foreach ($searchable_columns as $column) {
                    $query .= "$column LIKE '%%%s%' OR ";
                }
                $query = rtrim($query, "OR ");
                $query .= ")";
                $query = $wpdb->prepare($query, array_fill(0, count($searchable_columns), $search_term));
            }

            // Pagination setup
            $per_page = 20;
            $current_page = $this->get_pagenum();
            $total_items = $wpdb->query($query);
            $this->set_pagination_args(array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
            ));
            $query .= " LIMIT $per_page OFFSET " . ($current_page - 1) * $per_page;

            // Retrieve the data
            $this->items = $wpdb->get_results($query);

            // Define sortable columns
            $sortable = $this->get_sortable_columns();

            // Configure table headers
            $this->_column_headers = array($this->get_columns(), array(), $sortable);

            // Sort the items after setting column headers
            usort($this->items, array(&$this, 'usort_reorder'));
        }

        


        // Display the ID column
        function column_id($item) {
            return $item->id;
        }


        // Display the Rec number column
        function column_rec_id($item) {
            return $item->rec_id;
        }

        // Display the title column
        // function column_title($item) {
        //     return $item->title;
        // }

        function column_title($item) {
            $actions = array(
                      'edit'      => sprintf('<span class="update-action text-primary c-pointer"><a href="' . add_query_arg(array('entry_id' => $item->id), admin_url('admin.php?page=edit_publication')) . '"><i class="fas fa-edit"></i> Edit</a></span>',$_REQUEST['page'],'edit',$item->id),
                      'delete'    => sprintf('<span class="delete-action text-danger c-pointer" data-id="' . $item->id . '"><i class="fas fa-trash-alt"></i> Delete</span>',$_REQUEST['page'],'delete',$item->id),
                  );
          
            return sprintf('%1$s %2$s', $item->title, $this->row_actions($actions) );
        }

        // Display the ref_type column
        function column_ref_type($item) {
            return $item->ref_type;
        }

        // Display the year column
        function column_year_published($item) {
            return $item->year_published;
        }

        // Display the created_at column
        function column_created_at($item) {
            return $item->created_at;
        }

        // Display the last_updated column
        function column_last_updated($item) {
            return $item->last_updated;
        }

        // Display the status column
        function column_pub_status($item) {
            if( $item->pub_status === '0' ){
                return "<p class='badge badge-pill badge-warning'>Not Public</p>";
            } else {
                return "<p class='badge badge-pill badge-success text-white'>Public</p>";
            }
        }

        // function column_actions($item) {
        //     // Update, delete entry
        //     return '<div class="action-items">
        //                 <span class="update-action pr-3"><a href="' . add_query_arg(array('entry_id' => $item->id,'ref_type' => $item->ref_type), admin_url('admin.php?page=edit_publication')) . '"><i class="fas fa-edit" style="cursor:pointer"></i></a></span>
        //                 <span class="delete-action" data-id="' . $item->id . '"><i class="fas fa-trash-alt" style="color:red;cursor:pointer"></i></span>
        //             </div>';
        // }        

       
    }

    // Create an instance of our custom table class
    $wp_list_table = new Publications_List_Table();

    // Fetch the publications
    $wp_list_table->prepare_items();

    ?>

    <div class="row">
        <div class="col-md-6">
            <div class="metabox-holder">
                <ul class="subsubsub">
                    <li><span class='font-weight-bold'>All</span> <span class='count'>(<?php echo $total_count; ?>)</span></li>
                    <?php foreach ($counts as $count): ?>
                        <li><span class='pl-1 pr-1'>|</span><?php echo $count['ref_type']; ?> <span class='count'>(<?php echo $count['count']; ?>)</span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>">
                <?php $wp_list_table->search_box('Search', 'publication-search'); ?>
            </form>
        </div>
        <div class="col-md-6 pt-3">
            <form method="get" action="" class="form-inline">
                <div class="form-group">
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>">
                    <select id="ref_type_select" name="ref_type" class="form-control mr-3">
                        <option value="">All types</option>
                        <?php foreach ($counts as $count): ?>
                            <option value='<?php echo $count['ref_type']; ?>' <?php selected($_GET['ref_type'], $count['ref_type']); ?>><?php echo $count['ref_type']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select id="year_select" name="year_published" class="form-control mr-3">
                        <option value="">All years</option>
                        <?php foreach ($years as $year): ?>
                            <option value='<?php echo $year; ?>' <?php selected($_GET['year_published'], $year); ?>><?php echo $year; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-primary mr-3" type="submit">Filter</button> <button name="clear_filters" value="1" class="btn btn-outline-secondary">Clear Filters</button>
                </div>
             </form>
        </div>
        <div class="col-md-12">
            <?php $wp_list_table->display(); ?>
        </div>
    </div>
    
    <?php
}

function generateToggle($label, $info, $name, $values, $value, $required) {
    $html = '<div class="form-group row">
                <div class="col-sm-2 font-weight-bold">'. $label;
    if($required) {
        $html .= '<span class="text-danger"> * </span>';
    }
    $html .= '</div>
                    <div class="col-sm-10">
                        <div class="">';
    foreach ($values as $index => $item) {
        $html .= '<input type="checkbox" data-toggle="toggle" data-on="Public" data-off="Not Public" data-onstyle="success" data-offstyle="warning" name="' . $name . '" value="' . $item["value"] . '" ';
        if ($required && $index === 0) {
            $html .= 'required="' . $required . '"';
        }
        $html .= ($item["value"] === $value) ? ' checked' : '';
        $html .= '>';
        //$html .= '><label class="">'. $item["label"] . '</label>';
    }
    $html .= '</div><small><em>'. $info .'</em></small>
            </div>
        </div>';
    return $html;
}

function generateCheckbox($label, $info, $name, $values, $value, $required) {
    $html = '<div class="form-group row">
                <div class="col-sm-2 font-weight-bold">'. $label;
    if($required) {
        $html .= '<span class="text-danger"> * </span>';
    }
    $html .= '</div>
                    <div class="col-sm-10">
                        <div class="">';
    foreach ($values as $index => $item) {
        $html .= '<input type="checkbox" class="" name="' . $name . '[]" value="' . $item["value"] . '" ';
        if ($required && $index === 0) {
            $html .= 'required="' . $required . '"';
        }
        $html .= ($item["value"] === $value) ? ' checked' : '';
        $html .= '><label class="form-check-label">'. $item["label"] . '</label>';
    }
    $html .= '</div><small><em>'. $info .'</em></small>
            </div>
        </div>';
    return $html;
}

// function generateRadio($label, $name, $required){
//     $html = '<div class="form-group">
//                 <label>'. $label .'</label>';
//     if($required) {
//         $html .= '<span class="text-danger"> * </span>';
//     }
//     $html .= '<div id="'.$name.'_error"></div>
//                 <div class="form-check">
//                     <input class="form-check-input" type="radio" name="' . $name . '" value="Yes"';
//                     if ($required) {
//                         $html .= ' required="' . $required . '"';
//                     }
//     $html .= '><label class="form-check-label font-weight-normal">Yes</label>
//                 </div>
//                 <div class="form-check">
//                     <input class="form-check-input" type="radio" name="' . $name . '" value="No">
//                     <label class="form-check-label font-weight-normal">No</label>
//                 </div>
//             </div>';

//     if ($name !== 'newsletter' && $name !== 'administration' && $name !== 'monitor_connectivity' ){
//         $html .= '<div id="'. $name .'_yes_container" style="display:none">
//                 <div class="row">
//                     <div class="col-md-12">
//                         <div class="form-group">
//                             <label>'. SPECIFY .'<span class="text-danger"> * </span></label>
//                             <input type="text" id="' . $name . '_yes_answer" class="form-control" name="' . $name . '_yes_answer"';
//                             if ($required) {
//                                 $html .= ' required="' . $required . '"';
//                             }
//                             $html .= '/>
//                         </div>
//                     </div>
//                 </div>
//             </div>';
//     }
//     return $html;
// }

function generateInputYear($label, $info, $name, $value, $required){
    $html = '<div class="form-group row">
                <label class="col-sm-2 col-form-label font-weight-bold">'. esc_html($label);
    if($required) {
        $html .= '<span class="text-danger"> * </span>';
    }
    $html .= ' </label>
                <div class="col-sm-10">
                    <input type="number" min="1900" max="2099" step="1" id="'. esc_attr($name) .'" class="form-control" value="'. $value .'" name="'. esc_attr($name) .'" ';
    if ($required) {
        $html .= 'required="' . $required . '" ';
    }
        $html .= '/><small><em>'. esc_html($info) .'</em></small></div></div>';
    return $html;
}

function generateInput($label, $info, $name, $value, $required, $readonly, $type){
    $html = '<div class="form-group row">
                <label class="col-sm-2 col-form-label font-weight-bold">'. esc_html($label);
    if($required) {
        $html .= '<span class="text-danger"> * </span>';
    }
    $html .= ' </label>
                <div class="col-sm-10">
                    <input type="'.$type.'" id="'. esc_attr($name) .'" class="form-control" value="'. $value .'" name="'. esc_attr($name) .'" ';
    if ($required) {
        $html .= 'required="' . $required . '" ';
    }
    if ($readonly) {
        $html .= ' readonly';
    }
        $html .= '/><small><em>'. esc_html($info) .'</em></small></div></div>';
    return $html;
}

function generateTextarea($label, $info, $name, $value, $required){
    $html = '<div class="form-group row">
                <label class="col-sm-2 col-form-label font-weight-bold">'. esc_html($label);
    if($required) {
        $html .= '<span class="text-danger"> * </span>';
    }
    $html .= '</label>
                <div class="col-sm-10">
                <textarea id="'. esc_attr($name) .'" class="form-control" name="'. esc_attr($name) .'" rows="5" ';
    if ($required) {
        $html .= 'required="' . $required . '"';
    }
    $html .= '>'. $value .'</textarea><small><em>'. esc_html($info) .'</em></small>
            </div>
        </div>';
    return $html;
}

function generateRichTextarea($label, $info, $name, $value, $required){
    $editor_id = esc_attr($name);
    $settings = array(
        'textarea_name' => esc_attr($name),
        'textarea_rows' => 5,
        'teeny' => true,
        'media_buttons' => false,
    );
    ob_start();
    ?>
    <div class="form-group row">
        <label class="col-sm-2 col-form-label font-weight-bold">
            <?php echo esc_html($label); ?>
            <?php if ($required): ?>
                <span class="text-danger"> * </span>
            <?php endif; ?>
        </label>
        <div class="col-sm-10">
            <?php wp_editor($value, $editor_id, $settings); ?>
            <input type="hidden" id="<?php echo $editor_id; ?>_required" value="<?php echo $required ? 'true' : 'false'; ?>">
            <small id="<?php echo $editor_id; ?>_error" class="text-danger" style="display:none;">This field is required</small>
            <small><em><?php echo esc_html($info); ?></em></small>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    return $html;
}



function generateSelectbox($wpdb, $label, $info, $name, $values, $value, $required, $multiple){

    $html = '<div class="form-group row">
                <label class="col-sm-2 col-form-label font-weight-bold">'. esc_html($label);
    
    if($required) {
        $html .= '<span class="text-danger"> * </span>';
    }

    $multiple_brackets = ($multiple) ? '[]' : '';

    $html .= '</label>
                <div class="col-sm-6">
                    <select name="'. esc_attr($name) . $multiple_brackets.'" id="'. esc_attr($name) .'" class="form-control selectpicker" data-live-search="true" title="Nothing selected" ';
    
    if ($required) {
        $html .= 'required="required" ';
    }
    if ($multiple) {
        $html .= 'multiple';
    }

    $html .= '>';

    if ($values) {
        foreach ($values as $index => $item) {
            $selected = ($item["value"] == $value) ? ' selected' : '';
            $html .= '<option value="' . esc_attr($item["value"]) . '"' . $selected . '>' . esc_html($item["label"]) . '</option>';
        }
    }
    
    // Check the select box
    if ($name == "employees") {
        // Query all staff-manager posts
        $staff_query = new WP_Query(array(
            'post_type' => 'staff-manager',
            'posts_per_page' => -1 // all posts of the specified post type without any limit, any pagination
        ));

        if ($staff_query->have_posts()) {
            while ($staff_query->have_posts()) {
                $staff_query->the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();

                $staff_array = explode(',', $value);

                $selected = (in_array($post_id, $staff_array)) ? ' selected' : '';
                $html .= '<option value="' . $post_id . '"' . $selected . '>' . $post_title . '</option>';
            }
        }
    }
    if ($name == "departments") {
        // Query all group posts
        $group_query = new WP_Query(array(
            'post_type' => 'groups',
            'posts_per_page' => -1 // Retrieve all posts
        ));
    
        // Initialize arrays to hold parent and child pages
        $parent_pages = array();
        $child_pages = array();
    
        // Check if the query has posts
        if ($group_query->have_posts()) {
            // Retrieve all posts into an array
            $all_posts = $group_query->posts;
    
            // Reverse the array of posts
            $reversed_posts = array_reverse($all_posts);
    
            // Loop through the reversed posts
            foreach ($reversed_posts as $post) {
                setup_postdata($post);
                $post_id = $post->ID;
                $post_title = get_the_title($post_id);
                $post_parent = wp_get_post_parent_id($post_id);
    
                if ($post_parent == 0) {
                    $parent_pages[] = array(
                        'id' => $post_id,
                        'title' => $post_title
                    );
                } else {
                    $child_pages[] = array(
                        'id' => $post_id,
                        'title' => $post_title,
                        'parent' => $post_parent
                    );
                }
            }
            wp_reset_postdata(); // Reset the global post object
        }
    
        // Build the HTML structure
        foreach ($parent_pages as $parent) {
            $html .= '<optgroup label="' . esc_html($parent['title']) . '">';
            foreach ($child_pages as $child) {
                if ($child['parent'] == $parent['id']) {
                    $group_array = explode(',', $value);
                    $selected = (in_array($child['id'], $group_array)) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr($child['id']) . '"' . $selected . '>' . esc_html($child['title']) . '</option>';
                }
            }
            $html .= '</optgroup>';
        }
    }
    
    

    // Optionally, add more conditions for other types of select boxes
    // Example:
    // if ($name == "resp_institution" || $name == "other_institution") {
    //     $results = $wpdb->get_results("SELECT DISTINCT name FROM {$wpdb->prefix}institutions ORDER BY name ASC", ARRAY_A);
    //     foreach ($results as $row) {
    //         $html .= '<option value="' . esc_attr($row['name']) . '">' . esc_html($row['name']) . '</option>';
    //     }
    // }

    $html .= '</select><small><em>'. esc_html($info) .'</em></small></div>';

    // Optionally, add more HTML based on the type of select box
    // Example:
    // if ($name == "resp_institution" || $name == "other_institution") {
    //     $html .= '<div id="add_' . esc_attr($name) . '" class="mb-3"><span class="badge badge-light cursor-pointer"><i class="fas fa-plus-circle"></i> Add institution</span></div>';
    // }

    $html .= '</div>'; // Close the form group

    return $html;
}

?>