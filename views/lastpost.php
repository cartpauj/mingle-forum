<div class="poster_img_avatar">
  <?php echo $this->get_avatar($post->author_id, 25); ?>
</div>

<div class="wpf-item-poster">
  <div class="wpf-item-poster-li">
    <?php echo __("by", "mingleforum") . ' ' . $this->profile_link($post->author_id); ?>
  </div>

  <div class="wpf-item-poster-li">
    <a href="<?php echo $link; ?>"><?php echo date_i18n($this->options["forum_date_format"], strtotime($post->date)); ?>
      <img title="<?php echo __("Last post", "mingleforum"); ?>" style="vertical-align:middle;padding-left:5px;margin:-3px 0 0px 0;" src="<?php echo $this->skin_url; ?>/images/post/lastpost.png" />
    </a>
  </div>
</div>
