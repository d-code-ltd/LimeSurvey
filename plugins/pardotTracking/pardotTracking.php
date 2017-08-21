<?php
/**
 * Pardot tracking.
 *
 * @author Dragan <dragan@manufaktura.rs>
 * @version 1.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
class pardotTracking extends PluginBase
{

  static protected $name = 'pardotTracking';
  static protected $description = 'Pardot tracking functionality';

  protected $storage = 'DbStorage';
  protected $settings = array();

  public function init(){
    $this->subscribe('beforeSurveySettings');
    $this->subscribe('newSurveySettings');
    $this->subscribe('beforeSurveyPage'); 
  }

  /**
  * Load the tracking when the survey page loads and do the tracking on form submit
  */
  public function beforeSurveyPage() {
      $event = $this->event;
      $surveyId = $event->get('surveyId');
      $pardot = intval($this->get('pardot', 'Survey', $surveyId));
      $piAId = $this->get('piAId', 'Survey', $surveyId);
      $piCId = $this->get('piCId', 'Survey', $surveyId);

      if($pardot && strlen($piAId) && strlen($piCId)) {
        $pardot_track = "var piAId = '$piAId', piCId = '$piCId';
        (function() {
          function async_load(){
            var s = document.createElement('script'); s.type = 'text/javascript';
            s.src = ('https:' == document.location.protocol ? 'https://pi' : 'http://cdn') + '.pardot.com/pd.js';
            var c = document.getElementsByTagName('script')[0]; c.parentNode.insertBefore(s, c);
          }
          if(window.attachEvent) { window.attachEvent('onload', async_load); }
          else { window.addEventListener('load', async_load, false); }
        })();";

        Yii::app()->clientScript->registerScript(false, $pardot_track, CClientScript::POS_HEAD);

        $test = "var submitDirection = '';
        function serializeObject (array) {
            var json = {};
            $.each(array, function() {
                json[this.name] = this.value || '';
            });
            return json;
        }
        $(document).on('submit','#limesurvey',function(e) {
                var data = serializeObject($('#limesurvey').serializeArray());
                if (typeof piTracker == 'function') { 
                    piTracker(window.location.origin + '/' + data['sid'] + '/' + data['thisstep']);
                    console.log(window.location.origin + '/' + data['sid'] + '/' + data['thisstep']);
                }
        })";

        Yii::app()->clientScript->registerScript(false, $test, CClientScript::POS_END);
      }
  }

  /**
  * Show the plugin options in sruvey settings
  */
  public function beforeSurveySettings(){
      $event = $this->event;
      $event->set("surveysettings.{$this->id}", array(
          'name' => get_class($this),
          'settings' => array(
              'info' => array(
                  'type' => 'info',
                  'content' => 'Here you can set up your <strong>Pardot</strong> tracking.',
              ),
              'pardot'=>array(
                  'type'=>'boolean',
                  'label'=>'Active',
                  'help'=>'Activate/Deactivate the functionality',
                  'current' => $this->get('pardot', 'Survey', $event->get('survey')),
              ),
              'piAId'=>array(
                  'type'=>'string',
                  'label'=>'piAId',
                  'help'=>'Enter the "piAId" provided by pardot.com',
                  'current' => $this->get('piAId', 'Survey', $event->get('survey'))
              ),
              'piCId'=>array(
                  'type'=>'string',
                  'label'=>'piCId',
                  'help'=>'Enter the "piCId" provided by pardot.com',
                  'current' => $this->get('piCId', 'Survey', $event->get('survey'))
              )
          )
       ));
  }
  
  /**
  * Save the plugin data (stored in plugin_settings)
  */
  public function newSurveySettings(){
      $event = $this->event;
      foreach ($event->get('settings') as $name => $value) {
          $this->set($name, $value, 'Survey', $event->get('survey'));
      }
  }

}
