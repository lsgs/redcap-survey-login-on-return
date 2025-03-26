<?php
/**
 * REDCap External Module: Survey Login On Return
 * Provides option to use survey login only when returning to a survey containing data
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\SurveyLoginOnReturn;

use ExternalModules\AbstractExternalModule;

class SurveyLoginOnReturn extends AbstractExternalModule
{
    protected const SETTING_OPTION_LABEL = 'Survey Login not required to start survey.';
    protected const SETTING_DIALOG_TITLE = '<i class="fas fa-cube mr-1"></i>Survey Login on Return';
    protected const SETTING_ENABLE_SRL = 'You must first enable the "Save & Return Later" option.';
    protected const SETTING_HELP = '<p>When a survey has both Survey Login and Save & Return Later options enabled, this option prevents the Survey Login being required when initially accessing the survey (before any data is submitted).</p><p>In this mode, the Survey Login is a direct replacement for return codes.</p><p><i class="fas fa-cube text-info mr-1"></i>This feature is provided by an external module: <b>Survey Login on Return</b></p>';

    public function redcap_every_page_before_render($project_id) {
        if (!defined('PAGE')) return;
        if (PAGE=='surveys/index.php' && isset($_GET['s'])) $this->surveyPageBeforeRender(); 
        if (PAGE=='Surveys/edit_info.php' && isset($_GET['page']) && count($_POST)) $this->surveySettingsSave();
    }
    
    public function redcap_every_page_top($project_id) {

        if (!defined('PAGE')) return;
        if (PAGE=='Surveys/edit_info.php' && isset($_GET['page'])) $this->surveySettingsPageBeforeRender(); 
    }

    /**
     * surveyPageBeforeRender()
     * Are we on a survey page?
     * Does this survey have Survey Login AND the Save & Return Later enabled?
     * Is this survey fresh - no data yet submitted?
     * Does this survey have the module setting enabled?
     * --> skip survey login
     */
    protected function surveyPageBeforeRender() {
        global $Proj, $survey_auth_enabled, $survey_auth_enabled_single;

        if ($survey_auth_enabled==0 && $survey_auth_enabled_single==0) return;
        if (isset($_GET['__return']) && $_GET['__return']==1) return;
        
        $hash = $this->escape($_GET['s']);
        $sql = "select sr.record, s.form_name, sp.event_id, sr.instance, sr.start_time
                from redcap_surveys_participants sp 
                inner join redcap_surveys s on sp.survey_id=s.survey_id
                inner join redcap_surveys_response sr on sp.participant_id=sr.participant_id
                where sp.hash=? order by sr.start_time limit 1";
        $q = $this->query($sql, [$hash]);
        if ($q->num_rows===0) return;
        $r = db_fetch_assoc($q);

        $instrument = $r['form_name'];
        $timeStarted = $r['start_time'];

        $surveyId = $Proj->forms[$instrument]['survey_id'];
        if ($Proj->surveys[$surveyId]['save_and_return']!=1) return;

        $surveysEnabled = $this->getProjectSetting('survey-name');
        if (!in_array($instrument, $surveysEnabled)) return;


        if (empty($timeStarted) && ($survey_auth_enabled==1 || $survey_auth_enabled_single==1 || $Proj->project['survey_auth_enabled']==1)) {
            // survey has had no previous data entry - survey login is NOT required
            $survey_auth_enabled = $survey_auth_enabled_single = $Proj->project['survey_auth_enabled'] = 0;
        }
    }

    /**
     * surveySettingsPageBeforeRender()
     * Is survey login enabled in the project?
     * Are we on a survey settings page?
     * Show option in S&RL section for disabling Survey Login for the first page of the survey (SL only on return).
     */
    protected function surveySettingsPageBeforeRender() {
        global $Proj, $survey_auth_enabled, $survey_auth_enabled_single, $form;

        $instrument = $this->escape($_GET['page']);
        if ($instrument!==$form) return;

        $surveysEnabled = $this->getProjectSetting('survey-name');

        $isEnabled = (in_array($instrument, $surveysEnabled));

        // survey_617 = "NOTE: Survey Login will be utilized instead of return codes for this survey since Survey Login has been enabled for all surveys in the project."
        // survey_635 = "NOTE: Survey Login will be utilized instead of return codes for this survey since Survey Login has been enabled for this particular survey."

        echo '<div id="survey-login-on-return-container" style="font-weight:bold;margin-top:10px;color:#333;display:none;">
                <input name="survey_login_on_return" type="checkbox"> '.self::SETTING_OPTION_LABEL.' <a href="javascript:;" id="survey-login-on-return-help" class="help2" >?</a>
             </div>';

        $this->initializeJavascriptModuleObject();
        ?>
        <!-- SurveyLoginOnReturn: Begin -->
        <script type="text/javascript">
            $(function(){
                var module = <?=$this->getJavascriptModuleObjectName()?>;
                module.savedState = <?=($isEnabled)?'true':'false'?>;
                module.simpleDialogTitle = '<?=self::SETTING_DIALOG_TITLE?>';
                module.dialogTextEnableSRL = '<?=self::SETTING_ENABLE_SRL?>';
                module.dialogTextHelp = '<?=self::SETTING_HELP?>';

                module.optionSelect = function() {
                    if ($(this).prop('checked') && $('select[name=save_and_return]').val() == '0') {
                        $(this).prop('checked', false);
                        simpleDialog(module.dialogTextEnableSRL,module.simpleDialogTitle);
                    }
                };
                module.helpClick = function() {
                    simpleDialog(module.dialogTextHelp,module.simpleDialogTitle,600);
                };
                module.srlChange = function() {
                	if (this.value == '0') {
                        $('#save_and_return-tr input').prop('checked', false);
					}
				};
                module.init = function() {
                    $('input[name=survey_login_on_return').on('click', module.optionSelect).prop('checked', module.savedState);
                    $('select[name=save_and_return').on('change', module.srlChange);
                    $('#survey-login-on-return-help').on('click', module.helpClick);
                    $('#survey-login-on-return-container').insertAfter('#survey-login-note-save-return').show();
                };
                $(document).ready(function(){
                    module.init();
                });
            });
        </script>
        <!-- SurveyLoginOnReturn: End -->
        <?php
    }

    protected function surveySettingsSave() {
        global $Proj;
        $surveysEnabled = $this->getProjectSetting('survey-name');
        $instrument = $this->escape($_GET['page']);
        $idx = array_search($instrument, $surveysEnabled);

        $isEnabled = (isset($_POST['survey_login_on_return'])); // nb. empty checkboxes not present in $_POST

        if ($isEnabled) {
            if ($idx===false) $surveysEnabled[] = $instrument; // add instrument that is not present to enable
        } else {
            if ($idx!==false) unset($surveysEnabled[$idx]); // remove instrument that is present to disable
        }

        $surveysEnabled = array_values(array_filter($surveysEnabled, static function($var){return $var !== null;} ));
        if (empty($surveysEnabled)) $surveysEnabled[0] = null;

        $this->setProjectSetting('survey-name', $surveysEnabled);
    }
}