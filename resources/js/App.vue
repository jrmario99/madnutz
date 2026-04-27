<script setup>
import { onMounted } from 'vue';
import { useCustomer } from './stores/customer.js';
import AppToast   from './components/ui/AppToast.vue';
import AppConfirm from './components/ui/AppConfirm.vue';

const { fetchMe } = useCustomer();

onMounted(() => {
    fetchMe();
});
</script>

<template>
    <RouterView />
    <AppToast />
    <AppConfirm />
</template>
