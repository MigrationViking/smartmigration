

test expiration date expired
test forkert versionsnummer
test clear






Add an API endpoint to set the support and license information in the settingstable.
SupportName,SupportEmail,SupportCompay,LicenseKey,ExpirationDate



add a column to settings table RemoteSMVersion Text(10)
On the settings tab page add a section "SMART Migration Server" and under that section show tha value of RemoteSMVersion as "Version:"
On the Settings tab page add




Add to the app settings "RequiredSMVersion"   (SM is short for SMART Migration). set it to "7.96" now.

rød besked. hvis mismatch på home.


firma,email og navn under support.

i sm: rename sm mappen til Teams app og NC app

manular iframe.

ai frame


ved skift til onedrive do not include version history

vis type i oversigten

skift group hjælpetekst

hjælpetekst til titel



API:
in the api make a method to retrieve the "RequiredSMVersion","AppVersion"


digital resilence tab.


filter on ready,Finished,Running, error,OK,warning


en messagebox i toppen når den er ny eller mangelr licenskode. vis infor om process i en tab der er link til.
source URL/UNC i en source column i browse som man kan filtrere på


med eller uden groupware, filer
msg i topen når app ikke er initialized. som guilder til support tabben.


page 2 "Jobs" is a listt of jobs with filter columns that filter the defined jobs by typing into "Title Filter" (Text) , Description Filter" (Text), "Job Group Filter" (Dropdown with the valid groups based on the "Job Group" column")
(Just like Nextcloud Tables i nextcloud 34 - same UI except that rows may not be edited directly in ther list view.)
when a new row is added og edited a dialogbox opens to edit the job.

When a job runs the a row should be created in the "RunHistory" table. This happens when the jobs is updated to "Running" via the API (lets comback to the api later)
A link form the job to the Runhistory page so that when clicked the tab switched to "Run History" and filters out all runs for that job. Newest on top.

The job rows are created manually or by the API

When a job is deletes the runhistory should NOT be deleted.


page 3: "Run History" is a list of all runs for all jobs
The rows are created when the job start running - triggered by the api.
a row reflects the state the job was defined to when it ran. A multiline column shows all proerties for the job at that point in time like url , job mode etc.
The runhistory columsn displayed: title, desctription, jobgroup columns are joined from the "job" table.
Linked to the runhistory is a number of report files . the files are placed in a folder in the apoinbted Nextcloud Team folder for logs (settings tab)
When a job is updated by the api to completed then 







The table job is where a given jop is defined.

Eand row in the table define 1 job of:

When the "Advanced" is checked the more columns should appear.
The job can be updated form Discovery to Migration etc so it can change "JobMode"

Job Mode:
 1. Discover (Discovery) what files are at a given location FileShare or SharePoint Libraryy, Teams Library or onedrive.
    
   

 2. Migrate files (Migration) from or SharePoint Libraryy, Teams Library or onedrive to Nextcloud Teamfolder or Nextcloud User's personal files.
    (Job Mode: Ignore Offload..  and Restore.. job mode types. )



The job btable and and id column and an RunHistoryID column that links lob to runhistory 1:n

**********  PB Client *********

CBX_TeamsEnabled, CBX_NextcloudEnabled (system_cfg table)
- Styrer hvilke interfaces der køres


*********  Release *********
Set SM version: lib/AppInfo/Application.php:22