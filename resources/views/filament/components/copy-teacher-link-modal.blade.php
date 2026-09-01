<div x-data="{ copied: false }" class="space-y-4 py-2">
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Shareable Teacher Registration URL</label>
        <div class="flex items-center space-x-2">
            <input type="text" readonly value="{{ $link }}" id="teacher-reg-link-input" class="w-full text-sm font-mono bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-gray-800 focus:ring-indigo-500 focus:border-indigo-500">
            <button type="button"
                    @click="navigator.clipboard.writeText('{{ $link }}'); copied = true; setTimeout(() => copied = false, 3000)"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow-sm transition flex-shrink-0">
                <svg x-show="!copied" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <svg x-show="copied" x-cloak class="w-4 h-4 mr-1.5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="copied ? 'Copied!' : 'Copy Link'"></span>
            </button>
        </div>
    </div>
    <div class="bg-amber-50 border-l-4 border-amber-400 p-3 rounded text-xs text-amber-800">
        <strong>Tip:</strong> Copy this link and paste it in your teacher/staff WhatsApp group. When teachers register, they will appear in this table with inactive portal access. Simply click <strong>Activate Portal Access</strong> on any teacher's row to enable their access and send them a welcome email!
    </div>
</div>
