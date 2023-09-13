<?php
/**
 * (C) Copyright 2021 by Kolja Nolte
 * kolja.nolte@gmail.com
 * https://www.kolja-nolte.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * @see           https://wordpress.org/plugins/secondary-title/
 * @author        Kolja Nolte <kolja.nolte@gmail.com>
 *
 * This file handles everything within the "Settings" > "Secondary Title"
 * settings page within the admin area.
 *
 * @package       Secondary Title
 * @subpackage    Administration
 */

/**
 * Stop script when the file is called directly.
 *
 * @since 0.1.0
 */
if ( ! function_exists( "add_action" ) ) {
	die( "403 - You are not authorized to view this page." );
}

/**
 * Build the option page.
 *
 * @since 0.1.0
 */
function secondary_title_settings_page() {
	/** Restrict unauthorized access */
	if ( ! current_user_can( "manage_options" ) ) {
		wp_die( __( "You are not authorized to view this page.", "secondary-title" ) );
	}

	global $wp_version;

	/** Check if the submit button was hit and call is authorized */
	$reset_url = wp_nonce_url( get_admin_url() . "options-general.php?page=secondary-title&action=reset",
											"secondary_title_reset_settings",
											"nonce" );

	$saved = false;
	$reset = false;

	if ( $_SERVER["REQUEST_METHOD"] === "POST" && isset( $_POST["nonce"] )
	     && wp_verify_nonce( $_POST["nonce"],
																					"secondary_title_save_settings" )
	     && secondary_title_update_settings( $_POST )
	) {
		$saved = true;
	} elseif ( $_SERVER["REQUEST_METHOD"] === "GET" && isset( $_GET["action"], $_GET["nonce"] )
	           && wp_verify_nonce( $_GET["nonce"], "secondary_title_reset_settings" )
	) {
		if ( secondary_title_reset_settings() ) {
			$reset = true;
		}
	}

	$settings = secondary_title_get_settings( false );
	?>
	<form method="post" action="" class="wrap metabox-holder" id="secondary-title-settings">
		<input type="hidden"
		       id="text-confirm-reset"
		       value="<?php _e( "Are you sure you want to reset all settings?", "secondary-title" ); ?>"
		/>
		<h1 class="page-title">
			<i class="fa fa-cogs"></i>
			Secondary Title
		</h1>
		<?php
		if ( $saved ) {
			?>
			<div class="updated">
				<p>
					<i class="fa fa-check-circle"></i>
					<?php _e( "The settings have been successfully updated.", "secondary-title" ); ?>
				</p>
			</div>
			<?php
		} elseif ( $reset ) {
			?>
			<div class="updated">
				<p>
					<i class="fa fa-check-circle"></i>
					<?php _e( "All settings have been reset to their default values.", "secondary-title" ); ?>
				</p>
			</div>
			<?php
		}
		?>
		<section class="postboxes" id="postbox-general-settings">
			<div class="postbox">
				<h3 class="postbox-title hndle">
					<i class="fa fa-wrench"></i>
					<?php _e( "General Settings", "secondary-title" ); ?>
				</h3>
				<div class="inside">
					<table class="form-table">
						<tr id="row-auto-show">
							<th>
								<label for="auto-show-on">
									<i class="fa fa-magic"></i>
							<?php _e( "Auto show", "secondary-title" ); ?>:
								</label>
							</th>
							<td>
								<?php secondary_title_print_html_info_circle( "auto-show" ); ?>
								<div class="radios" id="auto-show">
									<input type="radio"
									       id="auto-show-on"
									       name="auto_show"
									       value="on"<?php checked( $settings["auto_show"], "on" ); ?>/>
									<label for="auto-show-on"><?php _e( "On", "secondary-title" ); ?></label>
									<input type="radio"
									       id="auto-show-off"
									       name="auto_show"
									       value="off"<?php checked( $settings["auto_show"], "off" ); ?>/>
									<label for="auto-show-off"><?php _e( "Off", "secondary-title" ); ?></label>
								</div>
								<p id="auto-show-on-description"
								   class="description"<?php checked( $settings["auto_show"], "off" ); ?>
								   hidden
								>
									<?php _e( "Automatically merges the secondary title with the standard title.", "secondary-title" ); ?>
								</p>
								<p id="auto-show-off-description" class="description">
									<?php
									echo sprintf( __( 'To manually insert the secondary title in your theme, use %s. See the <a href="%s" title="See official documentation" target="_blank" >official documentation</a> for additional parameters.',
																			"secondary-title" ),
																			"<code>&lt;?php echo get_secondary_title(); ?&gt;</code>",
																			"https://thaikolja.gitbooks.io/secondary-title/functions.html#get-secondary-title" );
									?>
								</p>
							</td>
						</tr>
						<tr id="row-title-format">
							<th>
								<label for="title-format">
									<i class="fa fa-keyboard"></i>
							<?php _e( "Title format", "secondary-title" ); ?>:
								</label>
							</th>
							<td>
								<input type="hidden"
								       id="title-format-backup"
								       value="<?php echo stripslashes( esc_attr( get_option( "secondary_title_title_format" ) ) ); ?>"
								/>
								<input type="text"
								       name="title_format"
								       id="title-format"
								       class="regular-text"
								       placeholder="<?php _e( "E.g.: %secondary_title%: %title%", "secondary-title" ); ?>"
								       value="<?php echo stripslashes( esc_attr( get_option( "secondary_title_title_format" ) ) ); ?>"
								       autocomplete="off"
								/>
								<p class="description">
									<?php
									echo sprintf( __( 'Replaces the default title with the given format. Use %s for the main title and %s for the secondary title.',
																													"secondary-title" ),
																			'<code class="pointer" title="' . __( "Add title to title format input",
																													"secondary-title" ) . '">%title%</code>',
																			'<code class="pointer" title="' . __( "Add secondary title to title format input",
																													"secondary-title" ) . '">%secondary_title%</code>' ); ?>
								</p>
								<div id="title-format-preview-container">
									<?php
									$random_post = get_random_post_with_secondary_title();

									if ( $random_post ) {
										$post_id = $random_post->ID;
										?>
										<input type="hidden" id="random-post-title" value="<?php echo get_the_title( $post_id ); ?>" />
										<input type="hidden"
										       id="random-post-secondary-title"
										       value="<?php echo get_secondary_title( $post_id ); ?>"
										/>
										<h4><?php _e( "Preview", "secondary-title" ); ?>:</h4>
										<div id="title-format-preview">
											<span class="text-field"></span>
										</div>
										<?php
									}
									?>
								</div>
								<p class="description note">
									<?php
									echo sprintf( __( '<b>Note:</b> To style the output, use <a href="%s" title="See an explanation on w3schools.com" target="_blank">HTML style attributes</a>, e.g.:<br />%s',
																			"secondary-title" ),
																			"https://www.w3schools.com/tags/att_global_style.asp",
																			'<code title="' . __( "Add code to title format input", "secondary-title" ) . '">'
																			. esc_attr( '<span style="color:#ff0000;font-size:14px;">%secondary_title%</span>' )
																			. "</code>" );
									?>
								</p>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<section class="postboxes" id="postbox-display-rules">
				<div class="postbox">
					<h3 class="postbox-title hndle">
						<i class="fa fa-eye"></i>
						<?php _e( "Display Rules", "secondary-title" ); ?>
					</h3>
					<div class="inside">
						<table class="form-table">
							<tr>
								<th>
									<i class="fa fa-filter"></i>
									<?php _e( "Only show in main post", "secondary-title" ); ?>:
								</th>
								<td>
									<?php secondary_title_print_html_info_circle( "only-show-in-main-post" ); ?>
									<div class="radios">
										<input type="radio"
										       id="only-show-in-main-post-on"
										       name="only_show_in_main_post"
										       value="on"<?php checked( $settings["only_show_in_main_post"], "on" ); ?>/>
										<label for="only-show-in-main-post-on"><?php _e( "On", "secondary-title" ); ?></label>
										<input type="radio"
										       id="only-show-in-main-post-off"
										       name="only_show_in_main_post"
										       value="off"<?php checked( $settings["only_show_in_main_post"], "off" ); ?>/>
										<label for="only-show-in-main-post-off"><?php _e( "Off", "secondary-title" ); ?></label>
									</div>
									<p class="description">
										<?php _e( "Only displays the secondary title if the post is a main post and <strong>not</strong> within a widget etc.",
																				"secondary-title" ); ?>
									</p>
								</td>
							</tr>
							<tr id="row-post-types">
								<th>
									<i class="fa fa-file-alt"></i>
									<?php _e( "Post types", "secondary-title" ); ?>:
								</th>
								<td>
									<?php secondary_title_print_html_info_circle( "post-types" ); ?>
									<div class="post-types">
										<?php
										$post_types = get_post_types( [ "public" => true ] );

										foreach ( $post_types as $post_type ) {
											if ( $post_type === "attachment" ) {
												continue;
											}

											$checked                 = "";
											$enabled_post_types      = secondary_title_get_setting( "post_types" );
											$post_type_object        = get_post_type_object( $post_type );
											$post_type_name_singular = $post_type_object->labels->singular_name;
											$post_type_name_plural   = $post_type_object->labels->name;
											$post_type_count         = wp_count_posts( $post_type )->publish;
											$post_type_count_label   = sprintf( _n( "1 $post_type_name_singular",
																															"%s $post_type_name_plural",
																															$post_type_count,
																															"secondary-title" ),
																					$post_type_count );

											if ( in_array( $post_type, $enabled_post_types, true ) ) {
												$checked = " checked";
											}
											?>
											<p class="post-type">
												<input type="checkbox"
												       name="post_types[]"
												       id="post-type-<?php echo $post_type; ?>"
												       value="<?php echo $post_type; ?>"<?php echo $checked; ?>/>
												<label for="post-type-<?php echo $post_type; ?>">
                                         <?php echo $post_type_object->labels->name; ?>
													 <small>(<?php echo $post_type_count_label; ?>)</small>
												 </label>
											</p>
											<?php
										}
										?>
									</div>
									<p class="description">
										<?php _e( "Only displays the secondary title if post among the selected post types. Select none for all.",
																				"secondary-title" ); ?>
									</p>
								</td>
							</tr>
							<tr id="row-categories">
								<th>
									<i class="fa fa-folder"></i>
									<?php _e( "Categories", "secondary-title" ); ?>:
								</th>
								<td>
									<?php secondary_title_print_html_info_circle( "categories" ); ?>
									<div class="list">
										<?php
										$categories = get_categories( [
																														"hide_empty" => false,
																				] );

										foreach ( $categories as $category ) {
											$allowed_categories = secondary_title_get_setting( "categories" );
											$checked            = "";

											if ( in_array( $category->term_id, $allowed_categories, false ) ) {
												$checked = " checked";
											}
											?>
											<div class="list-item"
											     title="<?php echo sprintf( __( "There are %s posts in %s", "secondary-title" ),
																					     $category->count,
																					     $category->name ); ?>"
											>
												<input type="checkbox"
												       name="categories[]"
												       id="category-<?php echo $category->term_id; ?>"
												       value="<?php echo $category->term_id; ?>"<?php echo $checked; ?>/>
												<label for="category-<?php echo $category->term_id; ?>">
										 <?php echo $category->name; ?>
												 </label>
											</div>
											<?php
										}
										?>
										<div class="clear"></div>
										<div class="list-actions">
                                    <span id="select-all-categories-container">
                                       <a href="#" id="select-all-categories">
                                          <?php _e( "Select all", "secondary-title" ); ?>
                                       </a>
                                    </span>
										</div>
										<p class="description">
											<?php _e( "Displays the secondary title only if post is among the selected categories. Select none for all.",
																					"secondary-title" ); ?>
										</p>
									</div>
								</td>
							</tr>
							<tr id="row-post-ids">
								<th>
									<label for="post-ids">
										<i class="fa fa-sort-numeric-up"></i>
							   <?php _e( "Post IDs", "secondary-title" ); ?>:
									</label>
								</th>
								<td>
									<input name="post_ids"
									       id="post-ids"
									       class="widefat"
									       placeholder="<?php _e( "E.g. 13, 71, 33", "secondary-title" ); ?>"
									       value="<?php echo implode( ", ", secondary_title_get_setting( "post_ids" ) ); ?>"
									/>
									<p class="description">
										<?php _e( "Only uses the secondary title if post is among the entered post IDs. Use commas to separate multiple IDs.",
																				"secondary-title" ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<section class="postboxes">
					<div class="postbox">
						<h3 class="postbox-title hndle">
							<i class="fa fa-cog"></i>
							<?php _e( "Miscellaneous Settings ", "secondary-title" ); ?>
						</h3>
						<div class="inside open">
							<table class="form-table">
								<tr>
									<th>
										<label for="include-in-search-on">
											<i class="fa fa-search"></i>
								  <?php _e( "Include in search", "secondary-title" ); ?>:
										</label>
									</th>
									<td>
										<?php secondary_title_print_html_info_circle( "include-in-search" ); ?>
										<div class="radios">
											<input type="radio"
											       name="include_in_search"
											       id="include-in-search-on"
											       value="on" <?php checked( $settings["include_in_search"], "on" ); ?>>
											<label for="include-in-search-on">
									 <?php _e( "On", "secondary-title" ); ?>
											</label>
											<input type="radio"
											       name="include_in_search"
											       id="include-in-search-off"
											       value="off" <?php checked( $settings["include_in_search"], "off" ); ?>>
											<label for="include-in-search-off">
									 <?php _e( "Off", "secondary-title" ); ?>
											</label>
										</div>
										<p class="description">
											<?php _e( "Makes the secondary title searchable.", "secondary-title" ); ?>
										</p>
									</td>
								</tr>
								<?php if ( version_compare( $wp_version, "5.0", ">=" ) && class_exists( "Classic_Editor" ) ) { ?>
									<tr>
										<th>
											 <label for="input-field-position-top">
												 <i class="fa fa-arrow-up"></i>
									  <?php _e( "Input field", "secondary-title" ); ?>:
											 </label>
										</th>
										<td>
											<?php secondary_title_print_html_info_circle( "input-fields" ); ?>
											<div class="radios">
												<input type="radio"
												       name="input_field_position"
												       id="input-field-position-top"
												       value="above" <?php checked( $settings["input_field_position"], "above" ); ?>/>
												<label for="input-field-position-top"><?php _e( "Above standard title",
																							"secondary-title" ); ?></label>
												<input type="radio"
												       name="input_field_position"
												       id="input-field-position-bottom"
												       value="below" <?php checked( $settings["input_field_position"], "below" ); ?>/>
												<label for="input-field-position-bottom"><?php _e( "Below standard title",
																							"secondary-title" ); ?></label>
											</div>
											<p class="description">
												<?php _e( "Determines the position of the secondary title input field on add/edit post pages within the admin area.",
																						"secondary-title" ); ?>
											</p>
											<p class="description">
												<small>
													<strong><?php _e( "Note", "secondary-title" ); ?>:</strong>
													<?php _e( "This option only applies when using the <em>Classic Editor</em> plugin.",
																							"secondary-title" ); ?>
												</small>
											</p>
										</td>
									</tr>
								<?php } ?>
								<tr>
									<th>
										<label for="column-position-left">
											<i class="fa fa-columns"></i>
								  <?php _e( "Column position", "secondary-title" ); ?>:
										</label>
									</th>
									<td>
										<?php secondary_title_print_html_info_circle( "column-position" ); ?>
										<div class="radios">
											<input type="radio"
											       name="column_position"
											       id="column-position-left"
											       value="left" <?php checked( $settings["column_position"], "left" ); ?>>
											<label for="column-position-left">
									 <?php _e( "Left of primary title", "secondary-title" ); ?>
											</label>
											<input type="radio"
											       name="column_position"
											       id="column-position-right"
											       value="right" <?php checked( $settings["column_position"], "right" ); ?>>
											<label for="column-position-right">
									 <?php _e( "Right of primary title", "secondary-title" ); ?>
											</label>
										</div>
										<p class="description">
											<?php echo sprintf( __( "Specifies the position of the secondary title in regard to the primary title on <a href=\"%s\">post overview</a> pages within the admin area.",
																					"secondary-title" ),
																					get_admin_url() . "edit.php" ); ?>
											<?php
											if ( version_compare( $wp_version, "5.0", ">=" ) ) {
												?>
												<strong><?php _e( "Note", "secondary-title" ); ?>:</strong>
												<?php
												_e( "This option only applies when using the Classic Editor plugin.", "secondary-title" );
											}
											?>
										</p>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</section>
			</section>
		</section>
		<div id="buttons" class="buttons">
			<button type="submit"
			        class="button button-primary"
			        title="<?php _e( "Click to save your changes", "secondary-title" ); ?>"
			>
				<i class="fa fa-save"></i>
				<?php _e( "Save Changes", "secondary-title" ); ?>
			</button>
			<a href="<?php echo $reset_url; ?>"
			   type="reset"
			   class="button reset-button"
			   title="<?php _e( "Click to reset settings to their default values", "secondary-title" ); ?>"
			>
				<i class="fa fa-redo"></i>
				<?php _e( "Reset Settings", "secondary-title" ); ?>
			</a>
			<a href="https://thaikolja.gitbooks.io/secondary-title/"
			   target="_blank"
			   type="button"
			   class="button"
			   title="<?php _e( "Click to view the full documentation of Secondary Title", "secondary-title" ); ?>"
			>
				<i class="fa fa-book"></i>
				<?php _e( "View Full Documentation", "secondary-title" ); ?>
			</a>
		</div>
		<?php wp_nonce_field( "secondary_title_save_settings", "nonce" ); ?>
		<div id="report-bug">
			<i class="fa fa-bug"></i>
			<?php echo sprintf( __( 'Found an error? Help making Secondary Title better by <a href="%s" title="Click here to report a bug" target="_blank">quickly reporting the bug</a>.',
													"secondary-title" ),
													"https://wordpress.org/support/plugin/secondary-title/#new-post" ); ?>
		</div>
	</form>
	<?php
}