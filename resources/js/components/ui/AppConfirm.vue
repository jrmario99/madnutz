<template>
    <Teleport to="body">
        <Transition name="confirm">
            <div v-if="state" class="fixed inset-0 z-[9998] flex items-center justify-center p-4"
                 style="background:rgba(0,0,0,0.6); backdrop-filter:blur(2px);"
                 @click.self="_cancel">

                <div class="bg-white w-full max-w-md"
                     style="border:3px solid #000; box-shadow:8px 8px 0px #000; border-radius:4px;">

                    <!-- Cabeçalho -->
                    <div class="px-6 pt-6 pb-4 border-b-3 border-black flex items-start gap-3"
                         :style="state.danger ? 'border-bottom:3px solid #C82830' : 'border-bottom:3px solid #000'">
                        <span class="text-2xl flex-shrink-0">
                            {{ state.danger ? '⚠' : '?' }}
                        </span>
                        <h2 class="font-bold uppercase tracking-wide leading-tight"
                            style="font-family:'Passion One',sans-serif; font-size:1.4rem;">
                            {{ state.title }}
                        </h2>
                    </div>

                    <!-- Mensagem -->
                    <div class="px-6 py-5">
                        <p class="text-gray-700" style="font-family:'Readex Pro',sans-serif; font-size:.95rem; line-height:1.6;">
                            {{ state.message }}
                        </p>
                    </div>

                    <!-- Ações -->
                    <div class="px-6 pb-6 flex gap-3 justify-end">
                        <button
                            @click="_cancel"
                            class="px-5 py-2.5 font-bold uppercase text-sm tracking-wide transition-all"
                            style="font-family:'Passion One',sans-serif; border:3px solid #000; background:#fff; box-shadow:3px 3px 0 #000;"
                            onmouseenter="this.style.transform='translate(-2px,-2px)';this.style.boxShadow='5px 5px 0 #000'"
                            onmouseleave="this.style.transform='';this.style.boxShadow='3px 3px 0 #000'"
                        >
                            {{ state.cancelLabel }}
                        </button>
                        <button
                            @click="_accept"
                            class="px-5 py-2.5 font-bold uppercase text-sm tracking-wide transition-all"
                            :style="confirmBtnStyle"
                            onmouseenter="this.style.transform='translate(-2px,-2px)';this.style.boxShadow='5px 5px 0 #000'"
                            onmouseleave="this.style.transform='';this.style.boxShadow='3px 3px 0 #000'"
                        >
                            {{ state.confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useConfirmDialog } from '../../composables/useConfirmDialog.js';

const { state, _accept, _cancel } = useConfirmDialog();

const confirmBtnStyle = computed(() => {
    const base = "font-family:'Passion One',sans-serif; border:3px solid #000; box-shadow:3px 3px 0 #000;";
    if (state.value?.danger) return base + ' background:#C82830; color:#fff;';
    return base + ' background:#FFDF00; color:#000;';
});
</script>

<style scoped>
.confirm-enter-active { transition: all .2s ease-out; }
.confirm-leave-active { transition: all .15s ease-in; }
.confirm-enter-from   { opacity: 0; }
.confirm-leave-to     { opacity: 0; }
.confirm-enter-active > div { transition: all .2s cubic-bezier(.34,1.56,.64,1); }
.confirm-enter-from   > div { transform: scale(.92) translateY(16px); }
</style>
