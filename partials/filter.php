<h1 class="display-5 mb-3 text-nowrap">Report Manager</h1>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 w-100">
    <div class="d-flex flex-column align-items-stretch gap-2 w-100" x-data="reportGenerator()">
        <div class="d-flex flex-column flex-md-row align-items-stretch gap-2">
            <div class="input-group input-group-lg">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="text-muted">📂</i> &nbsp; Source:</span>
                </div>
                <select x-model="sourceType" class="form-control form-control-lg custom-select">
                    <template x-for="type in sourceTypes" :key="type.value">
                        <option :value="type.value" x-text="type.label"></option>
                    </template>
                </select>
            </div>&nbsp;
            <div class="input-group input-group-lg">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="text-muted">📄</i> &nbsp; Report Type:</span>
                </div>
                <select x-model="reportType" class="form-control form-control-lg custom-select">
                    <template x-for="type in reportTypes" :key="type.value">
                        <option :value="type.value" x-text="type.label"></option>
                    </template>
                </select>
            </div>&nbsp;
            <button @click="generateReport" :disabled="generating" class="btn btn-success btn-lg shadow-sm px-4 report-get-btn" x-text="generating ? 'Generating...' : '🚀 Run Generate Report'"></button>
        </div>
        <!-- Show result message alert -->
        <div x-show="resultMessage" x-transition class="alert rounded-lg shadow-sm mb-0 w-100" :class="resultSuccess ? 'alert-success' : 'alert-danger'" x-html="resultMessage"></div>
    </div>
</div>