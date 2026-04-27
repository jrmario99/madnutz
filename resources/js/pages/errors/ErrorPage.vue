<template>
    <div class="min-h-screen flex flex-col items-center justify-center px-6 py-16 relative overflow-hidden"
         style="background:#f4f4f5;">

        <!-- Faixa decorativa -->
        <div class="absolute top-0 left-0 right-0 h-2" style="background:#C82830;" />

        <!-- Código de erro -->
        <p class="select-none leading-none font-bold"
           style="font-family:'Passion One',sans-serif;
                  font-size:clamp(7rem,22vw,14rem);
                  color:#C82830;
                  text-shadow: 6px 6px 0 #000;
                  line-height:1;">
            {{ code }}
        </p>

        <!-- Título -->
        <h1 class="mt-2 font-bold uppercase tracking-widest text-center"
            style="font-family:'Passion One',sans-serif; font-size:clamp(1.2rem,4vw,2rem); color:#000;">
            {{ info.title }}
        </h1>

        <!-- Descrição -->
        <p class="mt-3 text-center max-w-md"
           style="font-family:'Readex Pro',sans-serif; font-size:.95rem; color:#4C4C4C; line-height:1.7;">
            {{ info.description }}
        </p>

        <!-- Ações -->
        <div class="mt-8 flex flex-wrap gap-3 justify-center">
            <RouterLink to="/"
                class="px-6 py-3 font-bold uppercase tracking-wide text-sm transition-all"
                style="font-family:'Passion One',sans-serif;
                       background:#C82830; color:#fff;
                       border:3px solid #000; box-shadow:4px 4px 0 #000;"
                onmouseenter="this.style.transform='translate(-2px,-2px)';this.style.boxShadow='6px 6px 0 #000'"
                onmouseleave="this.style.transform='';this.style.boxShadow='4px 4px 0 #000'"
            >
                Ir para o início
            </RouterLink>
            <button
                v-if="showBack"
                @click="$router.back()"
                class="px-6 py-3 font-bold uppercase tracking-wide text-sm transition-all"
                style="font-family:'Passion One',sans-serif;
                       background:#fff; color:#000;
                       border:3px solid #000; box-shadow:4px 4px 0 #000;"
                onmouseenter="this.style.transform='translate(-2px,-2px)';this.style.boxShadow='6px 6px 0 #000'"
                onmouseleave="this.style.transform='';this.style.boxShadow='4px 4px 0 #000'"
            >
                Voltar
            </button>
        </div>

        <!-- Logo -->
        <div class="absolute bottom-6 flex items-center gap-2 opacity-40">
            <span style="font-family:'Passion One',sans-serif; font-size:.85rem; letter-spacing:.1em; color:#000;">MADNUTZ</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const props = defineProps({
    code: { type: [String, Number], default: null },
});

const route = useRoute();
const code  = computed(() => String(props.code ?? route.params.code ?? '404'));

const errors = {
    '401': {
        title:       'Acesso não autorizado',
        description: 'Você precisa estar logado para acessar esta página.',
    },
    '403': {
        title:       'Acesso proibido',
        description: 'Você não tem permissão para acessar este conteúdo.',
    },
    '404': {
        title:       'Página não encontrada',
        description: 'O endereço que você tentou acessar não existe ou foi removido.',
    },
    '500': {
        title:       'Erro interno do servidor',
        description: 'Algo deu errado no servidor. Nossa equipe já foi notificada.',
    },
    '503': {
        title:       'Em manutenção',
        description: 'O site está temporariamente fora do ar para manutenção. Volte em alguns minutos.',
    },
};

const info = computed(() => errors[code.value] ?? {
    title:       'Erro inesperado',
    description: 'Ocorreu um erro inesperado. Por favor, tente novamente.',
});

const showBack = computed(() => !['503'].includes(code.value));
</script>
