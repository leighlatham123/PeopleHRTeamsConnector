# PeopleHRTeamsConnector
Quick & dirty stand-alone code to create a new conversation in Microsoft Teams with calendar data from People HR (holidays + events)

Created this because when i looked, i couldn't find anything simple out there that would satisfy my requirements. Run it on a cronjob with php installed and call it at whichever interval suits your requirements.


**$peopleHrApiKey** = The API key generated from the People HR: https://help.peoplehr.com/en/articles/1970068-creating-an-api-key
**$teamsConnectorWebhookUrl** = The Microsoft Teams connector webhook: https://docs.microsoft.com/en-us/microsoftteams/office-365-custom-connectors

**$queryHolidaysName** = The name for the specific People HR 'holidays' query generated in People HR Admin: https://help.peoplehr.com/en/collections/101303-reports-queries-data. \
**$queryEventsName** = The name for the specific People HR 'other events' query generated in People HR Admin: https://help.peoplehr.com/en/collections/101303-reports-queries-data. \
