Lets make the "jobs" database table (make sure to create the table with compliant name).
The table will contain 3 job types: "Discovery" and "Test Migration", "Migration" based on the screenshots attached we will only focus on "Discovery" for now. . I have attached multiple screenshots showing different stes of columns to get the full picture for Discovery of all the columns
The column names should be Pascal notation so that "Scheduled Date" becomes "ScheduledDate" etc.

I want the columns names having "Job" in the name NOT to have "Job" in the name as this is implicit since the column is in the "Jobs" table. So [Job Mode] becomes [Mode].

I want the UI to create/edit the rows to be much like the Nextcloud Tables. So an editble table in the "Jobs" tab with a filter column to filter the job rows.
Except not all columns are in the visual table. Only [Title],[Mode],[Status],[Scheduled Date],[Result],[Group] The rest of the columns are only visible in the create/edit dialogbox.
Only [Title] and [Status] can be modifed in the browse view.

There should be a menu {...} (no visual brackets) on the top right of the visual table on top of the column names if not enouth space to display. Currently only 2 menu item namely "Create new","Import". lets define the actions for "Import" later. But the "Create new" menu item should create a new row. The creation or edit of a row should be in a dialogbox just like Nextcloud tables app. (later we will defines conditional validation and visibity for the columns in the create/edit dialogbox)

When a row has focus a menu {...} (no visual brackets) shows to the far right of the row. menu items: Edit,Copy,Delete to do the actions on the row. 
The description for each column (from the screenshots) goes into the descripion in the edit form.
The edit-control for each column in the dialogbox should be the same as on the screenshot eg. [Advanced Mode] is a checkbox. Except the SourceURL and SourceUNC columns are NOT multiline but single line evnen they display as multiline in the screenshot.
The [Mode] (Job Mode) should only have the following values: "Discovery","Test Migration","Migration"

CheckBox has the values: Yes,No

column data types:
ScheduledDate,FromDate,ToDate = DateTime
SizeFrom,SizeTo = Numeric(6)
Title = Text(255)
Description = Text(2500)
Group = Text(50)
AdvancedMode = Text(3)  
Status = Text(10)
Recurrence = Text(10)
Action = Text(15)
SourceType = Text(20)
SourceURL = Text(500)
SourceUNC = Text(1500)
SourceUPN = text(100)
SourceFileType = Text(500)
VersionHistoryScope = Text(30)

Default values when a new row is created buy the user or the API are as on the screenshot. ScheduledDate is CurrentDateTime as default.
We will make the API later so dont do that now.






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


