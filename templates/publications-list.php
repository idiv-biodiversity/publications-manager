<div class="filters row">
    <div class="col-md-10 has-search pl-0">
      <span class="fa fa-search form-control-feedback"></span>
      <input type="text" id="search-input" class="form-control" placeholder="<?php _e('Search by author or title', 'publications-manager'); ?>" onkeyup="filterPublications()">
    </div>
    <div class="col-md-2 pr-0">
      <select id="year-select" class="form-control" onchange="filterByYear()">
          <option value=""><?php _e('All years', 'publications-manager'); ?></option>
          <?php
          foreach ($years as $year) {
              echo '<option value="' . esc_attr($year) . '">' . esc_html($year) . '</option>';
          }
          ?>
      </select>
    </div>
</div>

<!-- No publications message -->
<div id="no-publications-message" style="display: none;">
    <?php _e('No iDiv publication found.', 'publications-manager'); ?>
</div>

<div id="publications-list">
    <?php 
    // Group publications by year
    $grouped_publications = [];
    foreach ($publications as $publication) {
        $grouped_publications[$publication->year_published][] = $publication;
    }

    // Sort years in descending order
    krsort($grouped_publications);

    foreach ($grouped_publications as $year => $publications_in_year) : ?>
        <div class="year-group" data-year="<?php echo esc_attr($year); ?>">
            <h5><?php _e('Publications', 'publications-manager'); ?> <?php echo esc_html($year); ?></h5>
            <?php foreach ($publications_in_year as $publication) : ?>
                <div class="publication-entry" data-title="<?php echo esc_attr($publication->title); ?>" data-authors="<?php echo esc_attr($publication->authors); ?>" data-year="<?php echo esc_attr($publication->year_published); ?>">
                    <?php if ($publication->ref_type == "Journal"): // Journal ?>
                        <p><?php echo wp_kses_post($publication->authors); ?> (<?php echo esc_html($publication->year_published); ?>): <?php echo esc_html($publication->title); ?>. <em><?php echo esc_html($publication->journal); ?></em></p>
                    <?php elseif ($publication->ref_type == "Book Section"): // Book section ?>
                        <p><?php echo wp_kses_post($publication->authors); ?> (<?php echo esc_html($publication->year_published); ?>): <?php echo esc_html($publication->title); ?>. In: <?php echo esc_html($publication->editors); ?> (Eds.) <em><?php echo esc_html($publication->book_title); ?></em>. <?php echo esc_html($publication->publisher); ?></p>
                    <?php elseif ($publication->ref_type == "Book/Report"): // Book/Report ?>
                        <p><?php echo wp_kses_post($publication->authors); ?> (Eds., <?php echo esc_html($publication->year_published); ?>): <?php echo esc_html($publication->title); ?>. <em><?php echo esc_html($publication->book_title); ?></em>. <?php echo esc_html($publication->publisher); ?></p>
                    <?php endif; ?>
                    <div class="links">
                        <?php if ($publication->doi_open_access == 1) : ?><span class="badge badge-success">Open Access</span><?php endif; ?>
                        <?php if (!empty($publication->doi_link)) : ?><a href="<?php echo esc_url($publication->doi_link); ?>" target="_blank">DOI</a><?php endif; ?>
                        <?php if (!empty($publication->pdf_link)) : ?>| <a href="<?php echo esc_url($publication->pdf_link); ?>" target="_blank">PDF</a><?php endif; ?>
                        <?php if (!empty($publication->data_link)) : ?>| <a href="<?php echo esc_url($publication->data_link); ?>" target="_blank">Data</a><?php endif; ?>
                        <?php if (!empty($publication->code_link)) : ?>| <a href="<?php echo esc_url($publication->code_link); ?>" target="_blank">Code</a><?php endif; ?>
                        <?php if (!empty($publication->custom_link)) : ?>| <a href="<?php echo esc_url($publication->custom_link); ?>" target="_blank"><?php echo esc_attr($publication->custom_link_name); ?></a><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

