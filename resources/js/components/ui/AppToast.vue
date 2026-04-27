<template>
    <Teleport to="body">
        <div class="fixed bottom-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none" style="max-width:380px; width:calc(100vw - 3rem)">
            <TransitionGroup name="toast">
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="flex items-start gap-3 p-4 pointer-events-auto cursor-pointer select-none"
                    :style="cardStyle(t.severity)"
                    @click="remove(t.id)"
                >
                    <!-- Ícone -->
                    <span class="text-xl flex-shrink-0 mt-0.5">{{ icon(t.severity) }}</span>

                    <!-- Texto -->
                    <div class="flex-1 min-w-0">
                        <p class="font-bold uppercase tracking-wide leading-tight"
                           style="font-family:'Passion One',sans-serif; font-size:1rem;">
                            {{ t.summary }}
                        </p>
                        <p v-if="t.detail" class="text-sm mt-0.5 opacity-90"
                           style="font-family:'Readex Pro',sans-serif;">
                            {{ t.detail }}
                        </p>
                    </div>

                    <!-- Fechar -->
                    <button class="flex-shrink-0 font-bold text-lg leading-none opacity-70 hover:opacity-100 transition-opacity">&times;</button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useToast } from '../../composables/useToast.js';

const { toasts, remove } = useToast();

const configs = {
    success: { bg: '#FFDF00', text: '#000000', border: '#000000' },
    error:   { bg: '#C82830', text: '#FFFFFF', border: '#000000' },
    warning: { bg: '#EF841A', text: '#000000', border: '#000000' },
    info:    { bg: '#000000', text: '#FFFFFF', border: '#000000' },
};

function cardStyle(severity) {
    const c = configs[severity] ?? configs.info;
    return {
        background:   c.bg,
        color:        c.text,
        border:       `3px solid ${c.border}`,
        boxShadow:    '5px 5px 0px #000',
        borderRadius: '4px',
    };
}

function icon(severity) {
    return { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' }[severity] ?? 'ℹ';
}
</script>

<style scoped>
.toast-enter-active { transition: all .25s cubic-bezier(.34,1.56,.64,1); }
.toast-leave-active { transition: all .2s ease-in; }
.toast-enter-from   { opacity: 0; transform: translateX(100%); }
.toast-leave-to     { opacity: 0; transform: translateX(110%) scale(.95); }
</style>
