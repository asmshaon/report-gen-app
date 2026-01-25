Instructions:
- Spend up to 15 hours. Deadline is January 30th. Project doesn't have to be fully completed, for example you can leave out the flipbook part. We have several candidates and need some hands on work to help find the best fit for us.
- Send finished code/files with any instructions on how to setup on our end. Also include anything that was not done/omitted due to time constraints.
- We should build this using php 5.5. If you have difficulty setting up an environment with that php version, use the earliest version of php that you can.
- Dont hesitate to reach out with any questions.

Project overview:
We will create an small app with a service that generates stock reports based on settings we configure.
	- App example: reportManager.html
	- Service output example: 6AIStocks.pdf, 6AIStocks.html. 6AIStocks flipbook.html
Basically the settings congigured via app will dictate the companies included and format of output of the reports.
Reports will be generated in 3 formats: HTML, PDF and flipbook.

Front end CRUD app - reportManager.php:
- Features:
	- Add/update/delete settings for report generation. Settings are stored in reportSettings.json
	- Generate reports button to run reports service.
	- Allows upload of images to /images folder and upload of pdf to /reports folder
- Settings:
	- Report File Name - eg. 6AIStocks
	- Report Title - eg. Today's Top 6 AI Stocks
	- Author Name - eg. Today's Top Stocks
	- API call - Just a placeholder field for now, API service will be integrated later. Use data.csv
	- Number of Stocks - eg. 6
	- PDF Cover Image - eg. see PDF
	- Article Image (180x180) - eg. articleImage.jpg
	- Disclaimer HTML - eg. disclaimer.html
	- Report intro HTML - eg. reportIntro.html
	- Stock Block HTML - eg. stockBlock.html
	- Upload PDF - uploads PDF to /reports/

Generate reports service - generateReports.php
- Uses reportSettings.json and data.csv to generate HTML, PDF and flipbook reports
	- It will generate report for each entry in reportSettings.json
	- Generated files are overwritten on every generation
	- Filenames are specified in settings
	- Number of stocks is used to limit the number of companies so if we set it to 3, the output would use top 3 companies from data.csv.
- Reports should be generated in /reports/ folder.
	- Example report output:
		- /reports/6AIStocks.html
		- /reports/6AIStocks.pdf
		- /reports/6AIStocks
- Use Trading View service for embedding charts - see 6AIStocks.html
	- https://www.tradingview.com/widget-docs/widgets/charts/symbol-overview/
- Following short codes should be supported:
	- [Current Date] - eg. format 2026-01-22 05:11:24
	- [Chart] - Trading view chart embed.
	- data.csv columns - [Company], [Exchange], [Ticker], [Price]....