********************************************************************************
# Survey Login On Return

Luke Stevens, Murdoch Children's Research Institute https://www.mcri.edu.au

[https://github.com/lsgs/redcap-survey-login-on-return/](https://github.com/lsgs/redcap-survey-login-on-return/)
********************************************************************************
## Summary of Functionality

When a survey has both **Survey Login** and **Save & Return Later** options enabled, this module provides an additional option for preventing the Survey Login being required when initially accessing the survey (before any data is submitted). 

> [ ] **Survey Login not required to start survey**

In this mode, the Survey Login is a direct replacement for the return codes.

## Configuration

The module's functionality can be enabled for a survey using the option that it makes available on the Survey Settings page. It is not necessary to access the module configuration via the External Modules Manager page in the project.

<img alt="Survey Settings Option" src="https://redcap.mcri.edu.au/surveys/index.php?pid=14961&__passthru=DataEntry%2Fimage_view.php&doc_id_hash=56b110945bb23df0551827a65f3416c46d857768&id=2086410&s=8NDfcDy4mmT6IBQX&page=file_page&record=10&event_id=47634&field_name=thefile&instance=1" />

### Notes
* If Survey Login is not enabled for the project then this module has no effect.
* If Save & Return Later is not enabled for a specific survey then this module has no effect for the survey.
* The option is available and operates in the same way whether Survey Login is enabled for all surveys in the project or just for the individual survey.

********************************************************************************