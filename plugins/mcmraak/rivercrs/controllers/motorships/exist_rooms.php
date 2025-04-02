<link rel="stylesheet" href="/plugins/mcmraak/rivercrs/assets/css/rivercrs.exist_rooms.css">
<div id="EX">
    <div @click="modal = true" class="btn btn-primary">Редактировать</div>
    <input type="hidden" name="Motorships[exist_rooms]" value='<?php echo $model->exist_rooms; ?>'>
    <input type="hidden" name="cabins_list" value='<?php echo $model->cabinsJson(); ?>'>
    <div v-if="modal" class="ex-modal">
        <div>
            <div class="ex-pan">
                <div @click="modal = false" class="ex-close">x</div>
            </div>
            <div class="ex-modal-body">
                <div class="ex-scheme">
                    <div @click.self="openPoint(point)" v-for="(point, key) in points" class="ex-point"
                         :class="(point.popup)?'selected':''"
                         :style="'width:'+point.w+'px;height:'+point.h+'px;margin-left:'+point.x+'px;margin-top:'+point.y+'px;'">

                        <div v-if="point.popup"
                             class="point-popup" :style="'margin-top:'+(1*point.h+10)+'px'">
                            <div @click="point.popup = false" class="point-close">x</div>
                            <div class="point-body">
                                <div class="point-label">Номер</div>
                                <input v-model="point.name" type="text">
                                <div class="point-label">Категория</div>
                                <select v-model="point.c">
                                    <option v-for="(name, id) in cabins_list" :value="id">{{ name }}</option>
                                </select>

                                <div class="point-label">Ширина</div>
                                <div class="axis">
                                    <div @click="point.w--" class="axis-u"><i class="icon-caret-left"></i></div>
                                    <input v-model="point.w" type="text">
                                    <div @click="point.w++" class="axis-d"><i class="icon-caret-right"></i></div>
                                </div>

                                <div class="point-label">Высота</div>
                                <div class="axis">
                                    <div @click="point.h--" class="axis-u"><i class="icon-caret-left"></i></div>
                                    <input v-model="point.h" type="text">
                                    <div @click="point.h++" class="axis-d"><i class="icon-caret-right"></i></div>
                                </div>

                                <div class="point-label">X</div>
                                <div class="axis">
                                    <div @click="point.x--" class="axis-u"><i class="icon-caret-left"></i></div>
                                    <input v-model="point.x" type="text">
                                    <div @click="point.x++" class="axis-d"><i class="icon-caret-right"></i></div>
                                </div>

                                <div class="point-label">Y</div>
                                <div class="axis">
                                    <div @click="point.y--" class="axis-u"><i class="icon-caret-left"></i></div>
                                    <input v-model="point.y" type="text">
                                    <div @click="point.y++" class="axis-d"><i class="icon-caret-right"></i></div>
                                </div>

                            </div>
                            <div @click="delPoint(key)" class="point-del">
                                Удалить
                            </div>
                        </div>
                        <div class="ex-point-name" :style="'height:'+point.h+'px'">
                            {{ point.name }}
                        </div>
                    </div>
                    <img id="Scheme" src="/storage/app/media<?php echo $model->exist_scheme; ?>">
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/plugins/mcmraak/rivercrs/assets/js/vue.min.js"></script>
<script src="/plugins/mcmraak/rivercrs/assets/js/rivercrs.exist_rooms.js"></script>