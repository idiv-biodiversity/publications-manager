<?php
global $wpdb;
$table_name = $wpdb->prefix . 'idiv_publications';


    // Handle edit form submission and update data in the database
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update-button']) && isset($_POST['edit_publication_nonce']) && wp_verify_nonce($_POST['edit_publication_nonce'], 'edit_publication_action')) {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = isset($_POST['title']) ? stripslashes(sanitize_text_field($_POST['title'])) : '';
        $ref_type = isset($_POST['ref_type']) ? sanitize_text_field($_POST['ref_type']) : '';
        $pub_status = isset($_POST['pub_status']) ? intval($_POST['pub_status']) : 0;

        $authors = isset($_POST['authors']) ? wp_kses_post($_POST['authors']) : '';
        $journal = isset($_POST['journal']) ? sanitize_text_field($_POST['journal']) : '';
        $editors = isset($_POST['editors']) ? sanitize_text_field($_POST['editors']) : '';
        $publisher = isset($_POST['publisher']) ? sanitize_text_field($_POST['publisher']) : '';
        $year_published = isset($_POST['year_published']) ? intval($_POST['year_published']) : 0;

        $doi_link = isset($_POST['doi_link']) ? sanitize_text_field($_POST['doi_link']) : '';
        $doi_open_access = isset($_POST['doi_open_access']) ? intval($_POST['doi_open_access']) : 0;
        $pdf_link = isset($_POST['pdf_link']) ? sanitize_text_field($_POST['pdf_link']) : '';
        $data_link = isset($_POST['data_link']) ? sanitize_text_field($_POST['data_link']) : '';
        $code_link = isset($_POST['code_link']) ? sanitize_text_field($_POST['code_link']) : '';
        $custom_link = isset($_POST['custom_link']) ? sanitize_text_field($_POST['custom_link']) : '';
        $custom_link_name = isset($_POST['custom_link_name']) ? sanitize_text_field($_POST['custom_link_name']) : '';

        $employees = isset($_POST['employees']) ? $_POST['employees'] : array();
        $sanitized_employees = array_map('sanitize_text_field', $employees);
        $employees_string = implode(',', $sanitized_employees);

        $departments = isset($_POST['departments']) ? $_POST['departments'] : array();
        $sanitized_departments = array_map('sanitize_text_field', $departments);
        $departments_string = implode(',', $sanitized_departments);

        $last_updated = date('Y-m-d H:i:s');


        if ($id > 0) {
            // Update data in the database
            $wpdb->update(
                $table_name,
                array(
                    'title' => $title,
                    'ref_type' => $ref_type,
                    'pub_status' => $pub_status,
                    'authors' => $authors,
                    'journal' => $journal,
                    'editors' => $editors,
                    'publisher' => $publisher,
                    'year_published' => $year_published,
                    'doi_link' => $doi_link,
                    'doi_open_access' => $doi_open_access,
                    'pdf_link' => $pdf_link,
                    'data_link' => $data_link,
                    'code_link' => $code_link,
                    'custom_link' => $custom_link,
                    'custom_link_name' => $custom_link_name,
                    'employees' => $employees_string,
                    'departments' => $departments_string,
                    'last_updated' => $last_updated,
                ),
                array('id' => $id)
            );
            echo '<div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Data successfully updated.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>';
        } else {
            echo '<div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Error updating data.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>';
        }
    }
    
    // Get the entry ID from the query parameter
    $entry_id = isset($_GET['entry_id']) ? intval($_GET['entry_id']) : 0;
    // Query to retrieve publication
    $query = "SELECT * FROM {$wpdb->prefix}idiv_publications WHERE id =".$entry_id;
    // Retrieve the data
    $result =  $wpdb->get_results($query);
    
    if (!empty($result)) { 
        
        foreach ($result as $pub) { ?>

            <form id="update-form" method="POST" action="">

                <div class="row mb-5">
                    <div class="col-md-6">
                        <h3>Edit publication</h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <img src="<?php echo plugin_dir_url(__FILE__) . 'img/logo.png'; ?>" alt="iDiv logo" class="img-fluid">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="hidden" name="edit_publication_nonce" value="<?php echo wp_create_nonce('edit_publication_action'); ?>">
                        <button type="submit" name="update-button" class="btn btn-primary update-button">Update</button>
                        <a class="btn btn-outline-primary" href="<?php echo admin_url('admin.php?page=publications_manager')?>" role="button">All publications</a>
                    </div>
                    <div class="col-md-6 text-right">
                        <p class="text-danger">* required fields</p>
                    </div>
                </div>
                <div class="row border-bottom pt-3">
                    <div class="col-md-12">
                        <?php echo generateInput("ID", NOT_EDITABLE, "id", esc_attr($pub->id), true, "readonly","text"); ?>
                        <?php echo generateInput("Record ID", NOT_EDITABLE, "rec_id", esc_attr($pub->rec_id), true, "disabled","text"); ?>
                        <?php echo generateToggle("Status",null,"pub_status", array(
                            array("value" => "1", "label" => "Public")
                        ), esc_attr($pub->pub_status), false); ?>
                    </div>
                </div>
                <div class="row pt-3">
                    <div class="col-md-12">
                        <?php 
                        echo generateInput("Title", null, "title", esc_attr($pub->title), true, null,"text"); 
                        echo generateSelectbox(null, "Reference Type", null, "ref_type", array(
                            array("value" => "Journal", "label" => "Journal"), 
                            array("value" => "Book Section", "label" => "Book Section"), 
                            array("value" => "Book/Report", "label" => "Book/Report")
                        ), esc_attr($pub->ref_type), true, false);
                        echo generateRichTextarea("Authors", null, "authors", wp_kses_post($pub->authors), true);

                        // Journal
                        if (esc_attr($pub->ref_type) == "Journal" ) echo generateInput("Journal", null, "journal", esc_attr($pub->journal), false, null,"text");
                        // Book title
                        if (esc_attr($pub->ref_type) == "Book Section" || esc_attr($pub->ref_type) == "Book/Report" ) echo generateInput("Book title", null, "book_title", esc_attr($pub->book_title), false, null,"text");
                        // Editors
                        if (esc_attr($pub->ref_type) == "Book Section" ) echo generateInput("Editors", null, "editors", esc_attr($pub->editors), false, null,"text");
                        // Publisher
                        if (esc_attr($pub->ref_type) == "Book Section" || esc_attr($pub->ref_type) == "Book/Report" ) echo generateInput("Publisher", null, "publisher", esc_attr($pub->publisher), false, null,"text"); 

                        echo generateInputYear("Year of publication", null, "year_published", esc_attr($pub->year_published), true);
                        echo generateInput("DOI link", DOI_FORMAT, "doi_link", esc_attr($pub->doi_link), false, null,"url");
                        echo generateCheckbox(null,null,"doi_open_access", array(
                                array("value" => "1", "label" => "Open access")
                            ), esc_attr($pub->doi_open_access), false);
                        echo generateInput("PDF link", URL_FORMAT, "pdf_link", esc_attr($pub->pdf_link), false, null,"url");
                        echo generateInput("Data link", URL_FORMAT, "data_link", esc_attr($pub->data_link), false, null,"url");
                        echo generateInput("Code link", URL_FORMAT, "code_link", esc_attr($pub->code_link), false, null,"url");
                        echo generateInput("Custom link", URL_FORMAT, "custom_link", esc_attr($pub->custom_link), false, null,"url");
                        echo generateInput("Custom link name", "Please specify here the title for the custom link", "custom_link_name", esc_attr($pub->custom_link_name), false, null,"text");
                        echo generateSelectbox($wpdb, "Employees", CHECK_ALL, "employees", null, esc_attr($pub->employees), false, true);
                        echo generateSelectbox($wpdb, "Departments", CHECK_ALL, "departments", null, esc_attr($pub->departments), false, true);
                        ?>
                    </div>
                </div>
            </form>
            <?php 
        } 
    }
?>