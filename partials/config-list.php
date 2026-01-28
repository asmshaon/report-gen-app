<div class="card" x-data="configManager()" x-init="init()">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Active Configurations</h5>
        <span class="badge badge-light">Stored in report_settings.json</span>
    </div>
    <div class="card-body p-0 position-relative">
        <!-- Loading overlay for fetching configurations -->
        <div x-show="loading" class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <div x-show="!loading">
            <!-- Generating report progress overlay -->
            <template x-if="generating">
                <div class="position-absolute bg-white bg-opacity-75 d-flex flex-column justify-content-center align-items-center" style="top: 0; left: 0; right: 0; bottom: 0; z-index: 10;">
                    <div class="spinner-border text-success" role="status">
                        <span class="sr-only">Generating...</span>
                    </div>
                    <p class="mt-3 text-success">Generating reports, please wait...</p>
                </div>
            </template>
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                <tr>
                    <th>Filename</th>
                    <th>Title</th>
                    <th>Stocks</th>
                    <th class="action-btns">Actions</th>
                </tr>
                </thead>
                <tbody>
                <template x-for="config in configurations" :key="config.id">
                    <tr>
                        <td><strong x-text="config.file_name"></strong></td>
                        <td x-text="config.title"></td>
                        <td x-text="config.number_of_stocks"></td>
                        <td class="action-btns">
                            <button class="btn btn-sm btn-outline-info" @click="editConfig(config)">Edit</button>
                            <button class="btn btn-sm btn-outline-success" @click="generateReport(config)" :disabled="generating">Generate</button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteConfig(config.id)">Delete</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="configurations.length === 0">
                    <td colspan="4" class="text-center text-muted p-4">No configurations found.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
