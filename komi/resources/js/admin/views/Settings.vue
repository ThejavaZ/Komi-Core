<template>
    <div class="max-w-2xl space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Autenticación de Dos Factores (2FA)</h2>

            <div class="flex items-center gap-3 mb-6">
                <span
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium',
                        twoFaEnabled
                            ? 'bg-green-100 text-green-700'
                            : 'bg-gray-100 text-gray-600',
                    ]"
                >
                    <span
                        :class="[
                            'w-2 h-2 rounded-full',
                            twoFaEnabled ? 'bg-green-500' : 'bg-gray-400',
                        ]"
                    />
                    {{ twoFaEnabled ? "Habilitado" : "Deshabilitado" }}
                </span>
            </div>

            <div v-if="successMsg" class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">
                {{ successMsg }}
            </div>
            <div v-if="errorMsg" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">
                {{ errorMsg }}
            </div>

            <div v-if="!twoFaEnabled && !setupData" class="space-y-4">
                <p class="text-sm text-gray-600">
                    La autenticación de dos factores añade una capa extra de seguridad a tu cuenta.
                </p>
                <button
                    @click="initSetup"
                    :disabled="loading"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
                >
                    Configurar 2FA
                </button>
            </div>

            <div v-if="setupData" class="space-y-6">
                <div>
                    <p class="text-sm text-gray-600 mb-3">
                        Escanea este código QR con tu aplicación de autenticación:
                    </p>
                    <div class="flex justify-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <img :src="setupData.qr_url" alt="QR Code" class="w-48 h-48" />
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-600 mb-1">
                        O ingresa esta clave manualmente:
                    </p>
                    <code class="block p-3 bg-gray-50 rounded-lg text-sm font-mono text-gray-800 border border-gray-200 select-all">
                        {{ setupData.secret }}
                    </code>
                </div>

                <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">
                        Código de verificación
                    </label>
                    <input
                        v-model="enableCode"
                        type="text"
                        maxlength="6"
                        placeholder="000000"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono tracking-widest"
                    />
                    <button
                        @click="enable2FA"
                        :disabled="loading || enableCode.length !== 6"
                        class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                    >
                        Habilitar
                    </button>
                </div>
            </div>

            <div v-if="twoFaEnabled" class="space-y-4">
                <p class="text-sm text-gray-600">
                    Para deshabilitar 2FA, ingresa tu código de verificación actual.
                </p>
                <div class="space-y-3">
                    <input
                        v-model="disableCode"
                        type="text"
                        maxlength="6"
                        placeholder="000000"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 font-mono tracking-widest"
                    />
                    <button
                        @click="disable2FA"
                        :disabled="loading || disableCode.length !== 6"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                    >
                        Deshabilitar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { showErrorToast } from '../api.js';
import { ref, onMounted } from "vue";
import { api } from "../api";

const twoFaEnabled = ref(false);
const setupData = ref(null);
const enableCode = ref("");
const disableCode = ref("");
const loading = ref(false);
const successMsg = ref("");
const errorMsg = ref("");

function clearMessages() {
    successMsg.value = "";
    errorMsg.value = "";
}

async function fetchStatus() {
    try {
        const res = await api("/admin/api/2fa/status");
        const data = await res.json();
        twoFaEnabled.value = data.enabled;
    } catch (e) {
    showErrorToast(e, 'Settings');
        // ignore
    }
}

async function initSetup() {
    clearMessages();
    loading.value = true;
    try {
        const res = await api("/admin/api/2fa/setup");
        const data = await res.json();
        if (res.ok) {
            setupData.value = {
                secret: data.secret,
                qr_url: `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.qr_code_url)}`,
            };
        } else {
            errorMsg.value = data.message || "Error al configurar 2FA.";
        }
    } catch (e) {
    showErrorToast(e, 'Settings');
        errorMsg.value = "Error al obtener la configuración de 2FA.";
    } finally {
        loading.value = false;
    }
}

async function enable2FA() {
    clearMessages();
    loading.value = true;
    try {
        const res = await api("/admin/api/2fa/enable", {
            method: "POST",
            body: JSON.stringify({ code: enableCode.value }),
        });
        const data = await res.json();
        if (res.ok) {
            twoFaEnabled.value = true;
            setupData.value = null;
            enableCode.value = "";
            successMsg.value = "2FA habilitado correctamente.";
        } else {
            errorMsg.value = data.message || "Código inválido.";
        }
    } catch (e) {
    showErrorToast(e, 'Settings');
        errorMsg.value = "Error al habilitar 2FA.";
    } finally {
        loading.value = false;
    }
}

async function disable2FA() {
    clearMessages();
    loading.value = true;
    try {
        const res = await api("/admin/api/2fa/disable", {
            method: "POST",
            body: JSON.stringify({ code: disableCode.value }),
        });
        const data = await res.json();
        if (res.ok) {
            twoFaEnabled.value = false;
            disableCode.value = "";
            successMsg.value = "2FA deshabilitado correctamente.";
        } else {
            errorMsg.value = data.message || "Código inválido.";
        }
    } catch (e) {
    showErrorToast(e, 'Settings');
        errorMsg.value = "Error al deshabilitar 2FA.";
    } finally {
        loading.value = false;
    }
}

onMounted(fetchStatus);
</script>
