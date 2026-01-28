// Global state store
const store = {
    configurations: [],
    refreshCallbacks: [],

    registerCallback(callback) {
        this.refreshCallbacks.push(callback);
    },

    notifyRefresh() {
        this.refreshCallbacks.forEach(function(cb) { cb(); });
    },

    setConfigurations(configs) {
        this.configurations = configs;
    },

    getConfigurations() {
        return this.configurations;
    }
};

function configManager() {
    return {
        configurations: [],
        loading: true,
        generating: false,

        init() {
            store.registerCallback(this.fetchFromStore.bind(this));
            this.fetchConfigurations();
        },

        fetchFromStore() {
            this.fetchConfigurations();
        },

        async fetchConfigurations() {
            try {
                const response = await fetch('app/api/get_configurations.php');
                if (!response.ok) throw new Error('Failed to fetch');
                const result = await response.json();
                this.configurations = result.data || [];
                store.setConfigurations(this.configurations);
            } catch (error) {
                console.error('Error loading configurations:', error);
                this.configurations = [];
            } finally {
                this.loading = false;
            }
        },

        editConfig(config) {
            window.dispatchEvent(new CustomEvent('edit-config', { detail: config }));
        },

        async deleteConfig(id) {
            const config = this.configurations.find(function(c) { return c.id === id; });
            const configName = config ? (config.title || config.file_name) : 'this configuration';
            if (!confirm('Are you sure you want to delete configuration : "' + configName + '"?')) return;

            try {
                const response = await fetch('app/api/delete_configuration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await response.json();
                if (result.success) {
                    await this.fetchConfigurations();
                } else {
                    alert(result.message || 'Failed to delete configuration');
                }
            } catch (error) {
                console.error('Error deleting configuration:', error);
                alert('Failed to delete configuration');
            }
        },

        async generateReport(config) {
            this.generating = true;

            try {
                const response = await fetch('app/api/generate_from_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: [config.id] })
                });
                const result = await response.json();
                if (result.success) {
                    var message = 'Reports generated successfully!';
                    if (result.data) {
                        var generated = result.data.generated;
                        var failed = result.data.failed || [];
                        if (generated && (generated.html || generated.pdf || generated.flipbook)) {
                            message = 'Successfully generated: ';
                            var types = [];
                            if (generated.html && generated.html.length > 0) types.push('HTML');
                            if (generated.pdf && generated.pdf.length > 0) types.push('PDF');
                            if (generated.flipbook && generated.flipbook.length > 0) types.push('Flipbook');
                            message += types.join(', ');
                        }
                        if (failed.length > 0) {
                            message += '\nFailed: ' + failed.join(', ');
                        }
                    }
                    alert(message);
                } else {
                    alert(result.message || 'Failed to generate report');
                }
            } catch (error) {
                console.error('Error generating report:', error);
                alert('Failed to generate report');
            } finally {
                this.generating = false;
            }
        }
    };
}

function formManager() {
    return {
        editingId: null,
        saving: false,
        articleImageError: '',
        pdfCoverError: '',
        manualPdfError: '',
        formData: {
            id: '',
            file_name: '',
            report_title: '',
            author_name: '',
            stock_count: 6,
            data_source: 'data.csv',
            images: {
                article_image: null,
                pdf_cover_image: null
            },
            content_templates: {
                intro_html: '',
                stock_block_html: '',
                disclaimer_html: ''
            }
        },

        init() {
            // Listen for edit events from config list
            window.addEventListener('edit-config', function(e) {
                this.loadConfig(e.detail);
            }.bind(this));
        },

        loadConfig(config) {
            this.editingId = config.id;
            this.formData = {
                id: config.id,
                file_name: config.file_name,
                report_title: config.title,
                author_name: config.author,
                stock_count: config.number_of_stocks,
                data_source: config.data_source,
                images: {
                    article_image: config.images.article_image || null,
                    pdf_cover_image: config.images.pdf_cover_image || null
                },
                content_templates: {
                    intro_html: config.content_templates.intro_html || '',
                    stock_block_html: config.content_templates.stock_block_html || '',
                    disclaimer_html: config.content_templates.disclaimer_html || ''
                }
            };
            // Scroll to form
            document.querySelector('.card.mb-5').scrollIntoView({ behavior: 'smooth' });
        },

        resetForm() {
            this.editingId = null;
            this.formData = {
                id: '',
                file_name: '',
                report_title: '',
                author_name: '',
                stock_count: 6,
                data_source: 'data.csv',
                images: {
                    article_image: null,
                    pdf_cover_image: null
                },
                content_templates: {
                    intro_html: '',
                    stock_block_html: '',
                    disclaimer_html: ''
                }
            };
            // Reset file inputs
            document.querySelectorAll('input[type="file"]').forEach(function(el) {
                el.value = '';
            });
            this.articleImageError = '';
            this.pdfCoverError = '';
            this.manualPdfError = '';
        },

        validateArticleImage(event) {
            const file = event.target.files[0];
            this.articleImageError = '';

            if (!file) return;

            // Check file size (max 1MB)
            const maxSize = 1 * 1024 * 1024; // 1MB in bytes
            if (file.size > maxSize) {
                this.articleImageError = 'File size must be less than 1MB';
                event.target.value = '';
                return;
            }

            // Check image dimensions (180x180) - warning only, don't block upload
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    if (img.width !== 180 || img.height !== 180) {
                        this.articleImageError = 'Warning: Recommended image size is 180x180 pixels (current: ' + img.width + 'x' + img.height + ')';
                    }
                }.bind(this);
                img.src = e.target.result;
            }.bind(this);
            reader.readAsDataURL(file);
        },

        validatePdfCover(event) {
            const file = event.target.files[0];
            this.pdfCoverError = '';

            if (!file) return;

            // Check file type (must be image)
            const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                this.pdfCoverError = 'Only image files are allowed (PNG, JPG, GIF, WEBP)';
                event.target.value = '';
                return;
            }

            // Check file size (max 2MB)
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            if (file.size > maxSize) {
                this.pdfCoverError = 'File size must be less than 2MB';
                event.target.value = '';
                return;
            }
        },

        validateManualPdf(event) {
            const file = event.target.files[0];
            this.manualPdfError = '';

            if (!file) return;

            // Check file type (must be PDF)
            if (file.type !== 'application/pdf') {
                this.manualPdfError = 'Only PDF files are allowed';
                event.target.value = '';
                return;
            }

            // Check file size (max 5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                this.manualPdfError = 'File size must be less than 5MB';
                event.target.value = '';
                return;
            }
        },

        async saveForm(event) {
            this.saving = true;

            try {
                const form = event.target;
                const formData = new FormData(form);

                const response = await fetch('app/api/post_configuration.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);

                    // Only reset form when creating new config, not when editing
                    if (!this.editingId) {
                        this.resetForm();
                    }

                    // Notify other components to refresh
                    store.notifyRefresh();
                } else {
                    alert(result.message || 'Failed to save configuration');
                }
            } catch (error) {
                console.error('Error saving configuration:', error);
                alert('Failed to save configuration');
            } finally {
                this.saving = false;
            }
        }
    };
}

function reportGenerator() {
    return {
        reportType: 'all',
        reportTypes: [
            { value: 'all', label: 'All Reports' },
            { value: 'html', label: 'HTML Report' },
            { value: 'pdf', label: 'PDF Report' },
            { value: 'flipbook', label: 'Flipbook Report' }
        ],
        generating: false,
        resultMessage: '',
        resultSuccess: false,

        async generateReport() {
            this.generating = true;
            this.resultMessage = '';

            try {
                // Get form data from the page
                const form = document.querySelector('form');
                if (!form) {
                    throw new Error('Form not found');
                }

                // Validate required fields
                const fileName = form.querySelector('[name="file_name"]').value;
                const stockCount = form.querySelector('[name="stock_count"]').value;

                if (!fileName || !stockCount) {
                    this.resultMessage = 'Please fill all required fields to generate reports.';
                    this.resultSuccess = false;
                    this.generating = false;
                    return;
                }

                // Collect form data
                const formData = new FormData(form);
                formData.append('report_type', this.reportType);

                const response = await fetch('app/api/generate_reports.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    let message = result.message || 'Report generated successfully';
                    const data = result.data || {};

                    // Handle a single file (html, pdf, flipbook)
                    if (data.file) {
                        message += '. <a href="reports/' + data.file + '" target="_blank" class="alert-link">View Report</a>';
                    } else if (data.html || data.pdf || data.flipbook) {
                        // Multiple files generated (report type: all)
                        const links = [];
                        if (data.html) {
                            links.push('<a href="reports/' + data.html + '" target="_blank" class="alert-link">HTML</a>');
                        }
                        if (data.pdf) {
                            links.push('<a href="reports/' + data.pdf + '" target="_blank" class="alert-link">PDF</a>');
                        }
                        if (data.flipbook) {
                            links.push('<a href="reports/' + data.flipbook + '" target="_blank" class="alert-link">Flipbook</a>');
                        }
                        if (links.length > 0) {
                            message += '. View: ' + links.join(' | ');
                        }

                        // Show any failed reports
                        if (data.failed && Object.keys(data.failed).length > 0) {
                            message += '<br><small class="text-muted">Failed: ' + Object.keys(data.failed).join(', ').toUpperCase() + '</small>';
                        }
                    }

                    this.resultMessage = message;
                    this.resultSuccess = true;
                } else {
                    this.resultMessage = result.message || 'Failed to generate report';
                    this.resultSuccess = false;
                }
            } catch (error) {
                console.error('Error generating report:', error);
                this.resultMessage = 'Failed to generate report: ' + error.message;
                this.resultSuccess = false;
            } finally {
                this.generating = false;
                // Auto-hide message after delay
                setTimeout(function() {
                    this.resultMessage = '';
                }.bind(this), !this.resultSuccess ? 5000 : 10000);
            }
        }
    };
}

function manualPdfUploader() {
    return {
        isOpen: false,
        fileName: '',
        uploading: false,
        uploadError: '',
        uploadSuccess: '',

        async uploadManualPdf(event) {
            this.uploading = true;
            this.uploadError = '';
            this.uploadSuccess = '';

            try {
                const form = event.target;
                const fileInput = form.querySelector('input[type="file"]');

                if (!fileInput.files || fileInput.files.length === 0) {
                    throw new Error('Please select a PDF file');
                }

                const formData = new FormData();
                formData.append('file_name', this.fileName);
                formData.append('pdf_file', fileInput.files[0]);

                const response = await fetch('app/api/upload_manual_pdf.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    this.uploadSuccess = result.message;
                    this.fileName = '';
                    fileInput.value = '';
                    setTimeout(function() {
                        this.uploadSuccess = '';
                    }.bind(this), 5000);
                } else {
                    this.uploadError = result.message || 'Failed to upload PDF';
                }
            } catch (error) {
                console.error('Error uploading PDF:', error);
                this.uploadError = 'Failed to upload PDF: ' + error.message;
            } finally {
                this.uploading = false;
            }
        }
    };
}
