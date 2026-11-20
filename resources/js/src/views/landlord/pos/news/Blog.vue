<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { BLOGS } from "@/services/endpoints/Ecommerce.js";
import BlogModal from "@/views/landlord/pos/news/BlogModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { useDeleteModal } from "@/composable/modal/useDeleteModal.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Edit, Trash } from "@iconoir/vue";
import AttachmentsColumn from "@/views/landlord/common/component/AttachmentsColumn.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref("blogs-table");

const options = ref({
	url: BLOGS,
	pageSize: 5,
	rowLimit: 10,
	orderBy: "desc",
	showGridView: false,
	actionType: "dropdown",
	paginationType: "pagination",
	enableRowSelect: false,
	enableBulkSelect: false,
	showSearch: true,
	showFilter: false,
	columns: [
		{ title: t("name"), type: "text", key: "name" },
		{ title: t("heading"), type: "text", key: "heading" },
		{
			title: t("attachments"),
			type: "component",
			key: "attachments",
			componentName: h(AttachmentsColumn),
		},
		{
			title: t("action"),
			type: "action",
			align: "right",
			permission: canAccess("update_blogs") || canAccess("delete_blogs"),
		},
	],
	actions: [
		{
			title: t("edit"),
			type: "edit",
			icon: markRaw(Edit),
			modifier: () => canAccess("update_blogs"),
		},
		{
			title: t("delete"),
			type: "delete",
			icon: markRaw(Trash),
			modifier: () => canAccess("delete_blogs"),
		},
	],
});

const { isModalActive, selectedData, closeModal } = useOpenModal();
const { deleteUrl, isDeleteModal, cancelled } = useDeleteModal();

const actionTiger = (row, action) => {
	if (action.type === "edit") {
		isModalActive.value = true;
		selectedData.value = `${BLOGS}/${row.id}`;
	} else if (action.type === "delete") {
		isDeleteModal.value = true;
		deleteUrl.value = `${BLOGS}/${row.id}`;
	}
};
</script>

<template>
	<div class="content-wrapper">
		<app-breadcrumb :page-title="$t('blogs')" />

		<app-datatable :id="tableId" :options="options" :add-button-text="$t('add_blog')"
			:button-visible="canAccess('create_blogs')" @buttonAction="isModalActive = true" @action="actionTiger" />

		<blog-modal v-if="isModalActive" :table-id="tableId" modal-id="blog-modal" :selected-url="selectedData"
			@close="closeModal" />

		<app-delete-modal v-if="isDeleteModal" :table-id="tableId" :selected-url="deleteUrl" @cancelled="cancelled" />
	</div>
</template>

<style scoped lang="scss"></style>
