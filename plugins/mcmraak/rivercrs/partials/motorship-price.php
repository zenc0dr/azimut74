<?php
/* [ JSON Structure of record in records ]
checkin_id (int)
waybill (string)
records
    checkin_id (int)
    cabin_id (int)
    cabin_name (string)
    price_a (int)
    price_b (int)
*/
?>
<link href="/plugins/mcmraak/rivercrs/assets/css/pricing.css" rel="stylesheet">
<meta motorship_id="<?php echo $model->id; ?>">
<h2>Теплоход: <?php echo $model->name; ?></h2>
<div id="RecordsPreloader" class="loading-indicator-container" style="display:none">
    <div class="loading-indicator">
        <span></span>
        <div>Загрузка цен...</div>
    </div>
</div>

<div id="Records" v-cloak style="display:none">
	<div v-for="record in records">
		<div class="pricingHeader">
			<div class="waybill-id">Заезд: id#{{ record.checkin_id }}</div>
			<div class="waybill-string" >Маршрут: <span v-html="record.waybill"></span></div>
		</div>
		<div class="pricingTable">
			<div class="titles">
				<div>Категория каюты</div>
				<div style="width:100px">Цена1</div>
				<div style="width:100px">Цена2</div>
			</div>
			<div class="items" v-for="record in record.records">
				<div v-bind:cabin_id="record.cabin_id">{{ record.cabin_name }}</div>
				<div class="cell"><input type="number" v-model="record.price_a" v-bind:value="record.price_a" /></div>
				<div class="cell"><input type="number" v-model="record.price_b" v-bind:value="record.price_b" /></div>
			</div>
		</div>
	</div>
</div>

<input type="hidden" name="Motorships[price]" />
<script src="/plugins/mcmraak/rivercrs/assets/js/motorship-price.js"></script>