<!-- Registration Modal -->
<div class="modal" id="modalRegisters" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        
            <div class="modal-header">
            	<img class="img-responsive" src="<?php echo Yii::app()->theme->baseUrl; ?>/img/tc-register.png">
                <button type="button" class="close close-feature" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="myModalLabel"><?php echo Yii::t('UserModule.views_auth_login', '<strong>
                    Join</strong> the TeachConnect Community') ?></h3>
            </div>
            
            <?php if ($canRegister) : ?>
                <?php
                $form = $this->beginWidget('CActiveForm', array(
                'id' => 'account-register-form',
                    'enableAjaxValidation'=>true,
//                    'clientOptions' => array(
//                        'validateOnSubmit' => true,
//                        'validateOnChange' => true,
//                    )
                ));
                ?>
            <div class="modal-body">


                <p class="text-center">
                    <?php echo Yii::t('UserModule.views_auth_login', "Join the community by entering your e-mail address below."); ?>
                </p>
                
                <div id="ie-alert-message" class="alert alert-danger" style="display:none;">
                    Unfortunately you will not be able to register using Internet Explorer at this time. Please use another browser such as Chrome or Firefox whilst we work on fixing TeachConnect for Internet Explorer.
                </div>
                <div class="row">
                    <div class="form-group col-sm-8 col-sm-offset-2">
                    	<!-- <input class="form-control" id="register-email" required placeholder="Enter your email" name="AccountRegisterForm[email]" value="" type="email"> -->
                        
                        <?php echo $form->textField($registerModel, 'email',
                            array(
                                'class' => 'form-control',
                                'required' => 'true',
                                'type' => 'email',
                                'placeholder' => Yii::t('UserModule.views_auth_login', 'email')
                                )
                            );
                        ?>
                        <?php echo $form->error($registerModel, 'email'); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">
                        <div class="terms-box">
                            <h4>Terms &amp; Conditions</h4>
                            <p>
                                <strong>HREC Approval Number</strong>: H14REA138<br>
                                <strong>Principal Researcher</strong>: Dr Nick Kelly
                            </p>    


                            <p>By creating an account and signing on to this website, you agree that data from your actions may be used in the research project Studying Support for Teachers in Transition.</p>
                            
                            <p>The data produced by your actions will be analysed and may be published by the researchers. Data will remain anonymous and by used in an unidentifiable way in published research.</p>


                            <p>Participation in the project through this website is entirely voluntary. If you do not wish to take part you are not obliged to. If you decide to take part and later change your mind, you are free to withdraw from the project at any stage. Any information already obtained from you will be destroyed.</p>
                            
                            
                            <p>Your decision whether to take part or not to take part, or to take part and then withdraw, will not affect your relationship with your university or with the teacher accreditation agencies. Please notify the researcher if you decide to withdraw from this project.</p>
                            
                            
                            <p>Should you have any queries regarding the progress or conduct of this research, you can contact the principal researcher:</p>
                            
                            <p><strong>Dr Nick Kelly</strong><br>
                            Australian Digital Futures Institute<br>
                            Y303, USQ, Toowoomba<br>
                            +61 7 4631 2718</p>
                            
                            
                           <p> If you have any ethical concerns with how the research is being conducted or any queries about your rights as a participant please feel free to contact the University of Southern Queensland Ethics Officer on the following details.</p>
                            
                            <p><strong>Ethics and Research Integrity Officer</strong>
                            Office of Research and Higher Degrees<br>
                            University of Southern Queensland<br>
                            West Street, Toowoomba 4350<br>
                            Ph: +61 7 4631 2690<br>
                            Email: <a href="mailto:ethics@usq.edu.au">ethics@usq.edu.au </a>
                            </p>
                            
                            <p>By clicking to agree I confirm that I have read and agree to the following statements:</p>
                            <ul>
                              <li>I have read the Participant Information Sheet and the nature and purpose of the research project has been explained to me. I understand and agree to take part.</li>
                              <li>I understand the purpose of the research project and my involvement in it.</li>
                              <li>I understand that I may withdraw from the research project at any stage and that this will not affect my status now or in the future.</li>
                              <li>I confirm that I am over 18 years of age.</li>
                              <li>I understand that while information gained during the study may be published, I will not be identified and my personal results will remain confidential.</li>
                              <li>I understand that the data from this project will be securely stored for a minimum of five years.</li>
                            </ul>
                            
                            <p>This information about consent can be found at any time in the “about” section of the website.</p>
               			</div>
               		</div>
               </div>
               
               <div class="row">
               		<div class="col-sm-8 col-sm-offset-2">
                       <div class="checkbox">
                            <label>
                              <input type="checkbox" required> I agree to the above terms &amp; conditions
                            </label>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="modal-footer">
            	<div class="row">
                	 <div class="col-sm-8 col-sm-offset-2">
                        <?php echo CHtml::submitButton(Yii::t('UserModule.views_auth_login', 'Register'), array('class' => 'btn btn-primary')); ?>
                        <?php $this->endWidget(); ?>
                        <?php endif; ?>
                	</div>
                </div>
            </div>
            
        </div>
    </div>
</div>
