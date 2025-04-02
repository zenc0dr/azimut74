<link href="/plugins/mcmraak/rivercrs/assets/css/pricing.css" rel="stylesheet">
<meta checkin_id="<?php echo $model->id; ?>">
<div id="RecordsPreloader" class="loading-indicator-container" style="display:none">
    <div class="loading-indicator">
        <span></span>
        <div>Загрузка цен...</div>
    </div>
</div>
<div id="Records" v-cloak style="display:none">
	<div class="pricingTable">
		<div class="titles">
			<div>Категория каюты</div>
			<div style="width:100px">Цена1</div>
			<div style="width:100px">Цена2</div>
		</div>
		<div class="items" v-for="record in records">
			<div v-bind:cabin_id="record.cabin_id">{{ record.cabin_name }}</div>
			<div class="cell"><input type="number" v-model="record.price_a" v-bind:value="record.price_a" /></div>
			<div class="cell"><input type="number" v-model="record.price_b" v-bind:value="record.price_b" /></div>
		</div>
	</div>
</div>


<input type="hidden" name="Checkins[pricing]" />
<script src="/plugins/mcmraak/rivercrs/assets/js/pricing.js"></script>
