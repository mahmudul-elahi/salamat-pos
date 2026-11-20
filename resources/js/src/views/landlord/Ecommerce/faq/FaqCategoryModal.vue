<script setup>
import {onMounted, ref} from "vue";
import Axios from "@/services/axios/index.js";
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {FAQ_CATEGORIES} from "@/services/endpoints/Ecommerce.js";

const props = defineProps({
	modalId: String,
	tableId: String,
	selectedUrl: String,
});

const emit = defineEmits(['close']);
const {preloader, errors, save, closeModal} = useSubmitForm(props, emit, 'no');

const formData = ref({
	name: '',
	order: 0,
	status: 1,
});

const statusOptions = [
	{id: 1, name: 'Active'},
	{id: 0, name: 'Inactive'},
];

const submit = () => {
	save(props.selectedUrl ? props.selectedUrl : FAQ_CATEGORIES, formData.value);
};

onMounted(async () => {
	if (!props.selectedUrl) return;

	const {data} = await Axios.get(props.selectedUrl);

	Object.assign(formData.value, data, {
		status: data.status ? 1 : 0,
	});
});
</script>

<template>
	<app-modal
		:modal-id="modalId"
		modal-size="large"
		:title="selectedUrl ? $t('update_faq_category') : $t('add_faq_category')"
		:preloader="preloader"
		@submit="submit"
		@close="closeModal">
		<template #body>
			<form>
				<div class="row g-4">
					<div class="col-md-12">
						<app-input type="text" id="name" :label="$t('name')" label-required :placeholder="$t('name')"
								   v-model="formData.name" :errors="$errors(errors, 'name')"/>
					</div>

					<div class="col-md-6">
						<app-input type="number" id="order" :label="$t('order')" :placeholder="$t('order')"
								   v-model="formData.order" :errors="$errors(errors, 'order')"/>
					</div>

					<div class="col-md-6">
						<app-input type="select" id="status"
                                   :label="$t('status')"
                                   list-value-field="name"
                                   :selected-disable="false"
								   :options="statusOptions"
                                   v-model="formData.status"
								   :errors="$errors(errors, 'status')"/>
					</div>
				</div>
			</form>
		</template>
	</app-modal>
</template>

<style scoped lang="scss"></style>

