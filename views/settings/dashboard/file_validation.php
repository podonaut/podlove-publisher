<script>
window.PODLOVE_DATA = window.PODLOVE_DATA || { baseUrl: <?php echo wp_json_encode(home_url()); ?> };
window.PODLOVE_DATA.asset_validation = <?php echo wp_json_encode($asset_validation_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<div data-client="podlove" style="margin: 15px 0;">
	<podlove-asset-validation></podlove-asset-validation>
</div>
