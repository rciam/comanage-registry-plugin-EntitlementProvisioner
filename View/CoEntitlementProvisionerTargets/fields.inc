<!--
/**
 * COmanage Registry CO Entitlement Provisioning Target Fields
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry 3.1.x
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
-->
<?php
// Determine if fields are editable
$e = false;

if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
  $e = true;

// We shouldn't get here if we don't have at least read permission, but check just in case

if(!$e && !$permissions['view'])
  return false;

// Add breadcrumbs
print $this->element("coCrumb");
// Provisioning Targes Crumb
$args = array();
$args['plugin'] = null;
$args['controller'] = 'co_provisioning_targets';
$args['action'] = 'index';
$args['co'] = $cur_co['Co']['id'];
$this->Html->addCrumb(_txt('ct.co_provisioning_targets.pl'), $args);

// Current Crumb
$crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_entitlement_provisioner_targets.1')));
$this->Html->addCrumb($crumbTxt);

print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
print $this->Form->hidden('co_provisioning_target_id', array('default' => $vv_ptid)) . "\n";

// Variables i want accross the view
$token_key = $this->request->params['_Token']['key'];

// https://dirac.egi.eu/files/diracVOs.json
// Load my css
//$this->Html->css('VomsProvisioner.bootstrap.min', array('inline' => false));
//$this->Html->css('VomsProvisioner.voms-provisioner',  array('inline' => false));
// Load my Javascript
//$this->Html->script('VomsProvisioner.bootstrap.min',  array('inline' => false));
//$this->Html->script('VomsProvisioner.jquery/jquery.base64',  array('inline' => false));
//$this->Html->script('VomsProvisioner.voms_provisioner_target',  array('inline' => false));
?>

<div id="<?php print $this->action; ?>_entitlement" class="explorerContainer">
    <div id="entitlementsProvisioner" class="personExplorer">
        <ul>
          <!-- Entitlement Config -->
          <li id="fields-database" class="fieldGroup">
            <a href="#tabs-database" class="fieldGroupName">
              <em class="material-icons">indeterminate_check_box</em>
              <?php
                print _txt('pl.entitlement_provisioner.db_settings');
              ?>
            </a>
            <div id="names-container" class="fields">
                <ul id="tabs-database" class="fields form-list">
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('hostname',_txt('pl.entitlement_provisioner.hostname')); ?>
                    <span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                  $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['hostname']) ? ''
                          : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['hostname'],FILTER_SANITIZE_SPECIAL_CHARS);
                  print $this->Form->input('hostname', array('size' => 50,'value' => $value));
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('port',_txt('fd.server.port')); ?>
                    </div>
                  </div>
                <div class="field-info">
                  <?php
                    $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['port']) ? ''
                             : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['port'],FILTER_SANITIZE_SPECIAL_CHARS);
                    $options = array(
                      'value' => $value,
                      'type'  => 'number',
                      'min'   => 1024,
                      'max'   => 65535);
                    print $this->Form->input('port', $options);
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print ($e ? $this->Form->label('type', _txt('fd.type')) : _txt('fd.type')); ?><span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    global $cm_lang, $cm_texts;
                    $attrs = array();
                    $attrs['value'] = (isset($co_entitlement_provisioner_targets) ? $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['type'] : '');
                    $attrs['empty'] = false;

                    if($e) {
                      print $this->Form->select('type',
                                                $vv_dbdriver_type_list,
                                                $attrs);

                      if($this->Form->isFieldError('type')) {
                        print $this->Form->error('type');
                      }
                    } else {
                      print _txt('en.server.sql', null, $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['type']);
                    }
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('username',_txt('fd.server.username')); ?>
                    <span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['username']) ? ''
                    : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['username'],FILTER_SANITIZE_SPECIAL_CHARS);
                    print $this->Form->input('username', array('size' => 50,'value' => $value));
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('password',_txt('fd.password')); ?>
                    <span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    $value = empty($vv_db_password) ? ''
                    : filter_var($vv_db_password,FILTER_SANITIZE_SPECIAL_CHARS);
                    print $this->Form->input('password', array('size' => 50,'value' => $value));
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('databas',_txt('pl.entitlement_provisioner.database')); ?>
                    <span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['databas']) ? ''
                    : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['databas'],FILTER_SANITIZE_SPECIAL_CHARS);
                    print $this->Form->input('databas', array('size' => 50,'value' => $value));
                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('persistent',_txt('fd.server.persistent')); ?>
                    <span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    $attrs = array();
                    $attrs['value'] = (isset($co_entitlement_provisioner_targets) ? $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['persistent'] : '');
                    $attrs['empty'] = false;

                    if($e) {
                      print $this->Form->checkbox('persistent',
                                                $vv_persistent_list,
                                                $attrs);

                      if($this->Form->isFieldError('persistent')) {
                        print $this->Form->error('persistent');
                      }
                    } else {
                      print _txt('en.server.sql', null, $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['persistent']);
                    }

                  ?>
                </div>
              </li>
              <li>
                <div class="field-name">
                  <div class="field-title">
                    <?php print $this->Form->label('encoding',_txt('fd.server.encoding')); ?>
                    <span class="required">*</span>
                  </div>
                </div>
                <div class="field-info">
                  <?php
                    $attrs = array();
                    $attrs['value'] = (isset($co_entitlement_provisioner_targets) ? $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['encoding'] : '');
                    $attrs['empty'] = false;

                    if($e) {
                      print $this->Form->select('encoding',
                                                $vv_encoding_list,
                                                $attrs);

                      if($this->Form->isFieldError('encoding')) {
                        print $this->Form->error('encoding');
                      }
                    } else {
                      print _txt('en.server.sql', null, $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['encoding']);
                    }

                  ?>
                </div>
              </li>
              <li class="fields-submit">
                  <div id="connection-test-lbl" class="field-name"></div>
                  <div class="field-info">
                      <button type='button'
                              id='connection-test-btn'
                              class='ui-button ui-corner-all ui-widget'>
                        <?php print _txt('fd.server.test_connection'); ?>
                      </button>
                  </div>
              </li>
            </ul>
            </div>
          </li>
           <li id="fields-stats" class="fieldGroup">
            <a href="#tabs-stats" class="fieldGroupName">
                  <em class="material-icons">indeterminate_check_box</em>
                  <?php print _txt('pl.entitlement_provisioner.pl_config'); ?>
            </a>
            <div id="names-container" class="fields">
              <ul id="tabs-stats" class="fields form-list">
                <li id="fields-stats" class="field-stack">      
                      <div class="field-name">
                        <div class="field-title">
                          <?php print $this->Form->label('vo_whitelist',_txt('pl.entitlement_provisioner.vo_whitelist')); ?>
                        </div>
                        <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.vo_whitelist.desc'); ?></div>
                      </div>
                      <div class="field-info">
                        <?php
                          $intro = empty($co_entitlement_provisioner_targets[0]['CoEntitlementProvisionerTarget']['vo_whitelist']) ? ""
                                  : filter_var($co_entitlement_provisioner_targets[0]['CoEntitlementProvisionerTarget']['vo_whitelist'],FILTER_SANITIZE_SPECIAL_CHARS);
                          print $this->Form->textarea('vo_whitelist', array('size' => 4000, 'rows' => 5, 'value' => $intro));
                        ?>
                      </div>
                  </li>
                  <li>
                      <div class="field-name">
                        <div class="field-title">
                          <?php print $this->Form->label('vo_roles',_txt('pl.entitlement_provisioner.vo_roles')); ?>
                          <span class="required">*</span>
                        </div>
                        <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.vo_roles.desc'); ?></div>
                      </div>
                      <div class="field-info">
                        <?php
                          $vo_roles_value = empty($co_entitlement_provisioner_targets[0]['CoEntitlementProvisionerTarget']['vo_roles']) ? ""
                                  : filter_var($co_entitlement_provisioner_targets[0]['CoEntitlementProvisionerTarget']['vo_roles'],FILTER_SANITIZE_SPECIAL_CHARS);
                          print $this->Form->input('vo_roles', array('size' => 256, 'value' => $vo_roles_value));
                        ?>
                      </div>
                  </li>
                  <li>
                  <div class="field-name">
                    <div class="field-title">
                      <?php print $this->Form->label('merge_entitlements',_txt('pl.entitlement_provisioner.merge_entitlements')); ?>
                    </div>
                    <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.merge_entitlements.desc'); ?></div>
                </div>
                <div class="field-info">
                    <?php
                    $attrs = array();
                    $attrs['value'] = (isset($co_entitlement_provisioner_targets) ? $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['merge_entitlements'] : '');
                    $attrs['empty'] = false;

                    if($e) {
                      print $this->Form->checkbox('CoEntitlementProvisionerTarget.merge_entitlements',
                                                $vv_persistent_list,
                                                $attrs);

                      if($this->Form->isFieldError('merge_entitlements')) {
                        print $this->Form->error('merge_entitlements');
                      }
                    } else {
                      print _txt('en.server.sql', null, $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['merge_entitlements']);
                    }

                  ?>
                </div>
                  </li>
                  <li>
                    <div class="field-name">
                        <div class="field-title">
                          <?php print $this->Form->label('urn_namespace',_txt('pl.entitlement_provisioner.urn_namespace')); ?>
                          <span class="required">*</span>
                        </div>
                        <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.urn_namespace.desc'); ?></div>
                      </div>
                      <div class="field-info">
                        <?php
                          $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['urn_namespace']) ? ''
                          : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['urn_namespace'],FILTER_SANITIZE_SPECIAL_CHARS);
                          print $this->Form->input('urn_namespace', array('size' => 50,'value' => $value));
                        ?>
                      </div>
                  </li>
                  <li>
                    <div class="field-name">
                        <div class="field-title">
                          <?php print $this->Form->label('urn_authority',_txt('pl.entitlement_provisioner.urn_authority')); ?>
                          <span class="required">*</span>
                        </div>
                        <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.urn_authority.desc'); ?></div>
                      </div>
                      <div class="field-info">
                        <?php
                          $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['urn_authority']) ? ''
                          : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['urn_authority'],FILTER_SANITIZE_SPECIAL_CHARS);
                          print $this->Form->input('urn_authority', array('size' => 50,'value' => $value));
                        ?>
                      </div>
                  </li>
                  <li>
                  <div class="field-name">
                      <div class="field-title">
                        <?php print $this->Form->label('urn_legacy',_txt('pl.entitlement_provisioner.urn_legacy')); ?>
                      </div>
                      <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.urn_legacy.desc'); ?></div>
                    </div>
                    <div class="field-info">
                      <?php
                  $attrs = array();
                  $attrs['value'] = (isset($co_entitlement_provisioner_targets) ? $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['urn_legacy'] : '');
                  $attrs['empty'] = false;

                  if($e) {
                    print $this->Form->checkbox('urn_legacy',
                                              $vv_persistent_list,
                                              $attrs);

                    if($this->Form->isFieldError('urn_legacy')) {
                      print $this->Form->error('urn_legacy');
                    }
                  } else {
                    print _txt('en.server.sql', null, $co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['urn_legacy']);
                  }

                ?>
                    </div>
                  </li>
                  <li>
                  <div class="field-name">
                      <div class="field-title">
                        <?php print $this->Form->label('vo_group_prefix',_txt('pl.entitlement_provisioner.vo_group_prefix')); ?>
                      </div>
                      <div class="field-desc"><?php print _txt('pl.entitlement_provisioner.vo_group_prefix.desc'); ?></div>
                    </div>
                    <div class="field-info">
                        <?php
                          $value = empty($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['vo_group_prefix']) ? ''
                          : filter_var($co_entitlement_provisioner_targets[0]["CoEntitlementProvisionerTarget"]['vo_group_prefix'],FILTER_SANITIZE_SPECIAL_CHARS);
                          print $this->Form->input('vo_group_prefix', array('size' => 50,'value' => $value));
                        ?>
                      </div>
                  </li>
                </ul>
            </div>
          </li>
          <li id="fields-btn" class="fieldGroup">  <!-- Save Button -->
            <div id="names-container" class="fields">
              <ul class="fields form-list">
                 <?php if($e): ?>
                      <li class="fields-submit">
                          <div class="field-name">
                              <span class="required"><?php print _txt('fd.req'); ?></span>
                          </div>
                          <div class="field-info">
                            <?php print $this->Form->submit($submit_label); ?>
                          </div>
                      </li>
                  <?php endif; ?>
              </ul>
            </div>
          </li>
        </ul>
    </div> <!-- personExplorer -->
<!-- This is currently not supported due to a bug in COmanage. --><?php //print $this->element("changelog"); ?>
</div> <!-- explorerContainer -->
<script type="text/javascript">
    function responseConstruct(msg, status, testBtn) {
        color = (status === 'error') ? 'red' : 'green';
        text = "<span style='color:" + color + "'>" + msg + "</span>";
        $("#connection-test-lbl").html(text);

        testBtn.toggleClass('ui-state-disabled');
        testBtn.find('span').remove();
        testBtn.text("<?php print _txt('fd.server.test_connection'); ?>");
        testBtn.removeAttr('disabled');
    }
    
    <!-- /* JS specific to these fields */ -->
    $(function() {
      // Explorer menu toggles
      $(".fieldGroupName").click(function(event) {
          event.preventDefault();
          $(this).next(".fields").slideToggle("fast");
          // toggle the +/- icon:
          if ($(this).find(".material-icons").text() == "indeterminate_check_box") {
            $(this).find(".material-icons").text("add_box");
          } else {
            $(this).find(".material-icons").text("indeterminate_check_box");
          }
        });
      
      // Update the default value each time i change
      $('input').on('keyup', function() {
          this.defaultValue = this.value;
      });
      $('select').on('change', function() {
          $("option[value=" + this.value + "]", this)
              .attr("selected", true).siblings()
              .removeAttr("selected");
      });

      $("#connection-test-btn").click(function(e) {
          // Toggle the button
          var testBtn = $(this);
          var loadingSpan = $("<i class='fa fa-spinner fa-spin'></i>");
          testBtn.text('Connecting..');
          testBtn.toggleClass('ui-state-disabled');
          testBtn.prepend(loadingSpan);
          testBtn.attr('disabled', true);
          
          model = '<?php print Inflector::singularize($model);?>';
          // The data we will Post to COmanage. We include the token as well.
          var db_config = {
              _Token: {}
          };
          db_config.host = $("input[name='data[" + model + "][hostname]']").attr('value');
          db_config.port = $("input[name='data[" + model + "][port]']").attr('value');
          db_config.database = $("input[name='data[" + model + "][databas]']").attr('value');
          db_config.persistent = $("select[name='data[" + model + "][persistent]'] option:selected").attr('value');
          db_config.password = $("input[name='data[" + model + "][password]']").attr('value');
          db_config.datasource = $("select[name='data[" + model + "][type]'] option:selected").attr('value');
          db_config.encoding = $("select[name='data[" + model + "][encoding]'] option:selected").attr('value');
          db_config.login = $("input[name='data[" + model + "][username]']").attr('value');
          db_config._Token.key = '<?php echo $token_key;?>';

          $.ajax({
              type: "POST",
              url: '<?php echo $this->Html->url(array(
                'plugin' => Inflector::singularize(Inflector::tableize($this->plugin)),
                'controller' => 'co_entitlement_provisioner_targets',
                'action' => 'testconnection',
                'co'  => $cur_co['Co']['id'])); ?>',
              beforeSend: function(xhr) {
                  xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                  xhr.setRequestHeader('X-CSRF-Token', '<?php echo $token_key;?>');
              },
              cache:false,
              data: db_config,
              success: function(response) {
                  responseConstruct(response.msg, response.status, testBtn);
              },
              error: function(jqxhr) {
                  var response = jqxhr.responseJSON;
                  if(jqxhr.status === 401) {
                      // Reload page
                      location.reload();
                  } else {
                      responseConstruct(response.msg, response.status, testBtn);
                  }
              }
          }); // ajax
      }); // Click event
    });
</script>