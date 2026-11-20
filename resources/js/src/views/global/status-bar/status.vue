<script setup lang="ts">
import { Bell, CheckCircle, Clock, EmptyPage, MultiplePages } from '@iconoir/vue';

</script>

<template>
    <div>
        <div class="status-bar">
            <!-- card items  -->
            <div class="items">
                <div class="left">
                    <div class="icon-box">
                        <MultiplePages />
                    </div>
                </div>
                <div class="right">
                    <div class="total">Total</div>
                    <div class="invoices"><b>24</b><span class="ms-2">Invoices</span></div>
                    <div class="amount">$275.43</div>
                </div>
            </div>
            <div class="items">
                <div class="left">
                    <div class="icon-box">
                        <CheckCircle />
                    </div>
                </div>
                <div class="right">
                    <div class="total">paid</div>
                    <div class="invoices"><b>4</b><span class="ms-2">Invoices</span></div>
                    <div class="amount">$446.26</div>
                </div>
            </div>
            <div class="items">
                <div class="left">
                    <div class="icon-box">
                        <Clock />
                    </div>
                </div>
                <div class="right">
                    <div class="total">unpaid</div>
                    <div class="invoices"><b>8</b><span class="ms-2">Invoices</span></div>
                    <div class="amount">$490</div>
                </div>
            </div>
            <div class="items">
                <div class="left">
                    <div class="icon-box">
                        <Bell />
                    </div>
                </div>
                <div class="right">
                    <div class="total">overdue</div>
                    <div class="invoices"><b>8</b><span class="ms-2">Invoices</span></div>
                    <div class="amount">$601.43</div>
                </div>
            </div>
            <div class="items">
                <div class="left">
                    <div class="icon-box">
                        <EmptyPage />
                    </div>
                </div>
                <div class="right">
                    <div class="total">draft</div>
                    <div class="invoices"><b>4</b><span class="ms-2">Invoices</span></div>
                    <div class="amount">$739.43</div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
