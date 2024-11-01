<?php
! defined( 'ABSPATH' ) && exit();
 use TotalSurvey\Models\Section;
use TotalSurvey\Models\Survey;
use TotalSurvey\Tasks\Utils\GetAllowedSurveyTags;
use TotalSurveyVendors\TotalSuite\Foundation\Support\Collection;
use TotalSurveyVendors\TotalSuite\Foundation\WordPress\Modules\Template;

/**
 * @var string $apiBase
 * @var string $nonce
 * @var Template $template
 * @var Survey $survey
 * @var string $customCss
 * @var string $language
 * @var string $direction
 * @var Collection | Section $sections
 * @var string $survey
 * @var Section $section
 * @var Template $template
 */
?>
<template data-survey-uid="<?php echo esc_attr( $survey->uid ); ?>" class="totalsurvey-template">
    <survey-model>
        <?php echo json_encode( $survey ); ?>
    </survey-model>

    <!--  style   -->
	<?php echo wp_kses(
		$template->view( 'partials/style' ), [
		'survey-link'  => [
			'rel'  => [],
			'href' => [],
			'type' => [],
		],
		'survey-style' => [
			'hidden' => [],
		],
	] );
	?>

	<?php echo esc_html( $before ); ?>

    <!--  survey  -->
    <survey
            inline-template
            nonce="<?php echo esc_attr( $nonce ); ?>"
            language="<?php echo esc_attr( $language ); ?>"
            dir="<?php echo esc_attr( $direction ); ?>"
            v-bind:api-base="'<?php echo esc_attr( $apiBase ); ?>'"
            id="survey">
        <div class="survey" v-bind:class="{'is-done': isFinished}" <?php language_attributes(); ?>>
            <div class="survey-header">
                <h3 class="survey-title" v-text="survey.name"><?php echo esc_html( $survey->name ) ?></h3>
                <?php if($survey->isLanguageSwitcherEnabled()): ?>
                <?php $availableLanguages = $survey->getAvailableLanguages(); ?>
                <?php if(!empty($availableLanguages)): ?>
                    <div class="survey-language-switcher">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" width="24px" fill="#000000">
                            <path d="M10.9,20l4.55-12h2.1l4.55,12h-2.1l-1.08-3.05h-4.85l-1.08,3.05h-2.1ZM3,17l-1.4-1.4,5.05-5.05c-.58-.58-1.11-1.25-1.59-2s-.91-1.6-1.31-2.55h2.1c.33.65.67,1.22,1,1.7s.73.97,1.2,1.45c.55-.55,1.12-1.32,1.71-2.31s1.04-1.94,1.34-2.84H0v-2h7V0h2v2h7v2h-2.9c-.35,1.2-.88,2.43-1.58,3.7s-1.39,2.23-2.08,2.9l2.4,2.45-.75,2.05-3.05-3.13-5.05,5.03ZM14.7,15.2h3.6l-1.8-5.1-1.8,5.1Z"
                                  fill="#000000" stroke-width="0"/>
                        </svg>
                        <select v-on:change="switchLanguage($event.target.value)">
                            <?php foreach($availableLanguages as $language): ?>
                                <option value="<?php echo esc_attr($language['code']); ?>" <?php selected(get_locale(), $language['code']) ?>>
                                    <?php echo esc_html($language['name']); ?>
                                    <?php if($language['name'] !== $language['native_name']): ?>
                                        - <?php echo esc_html($language['native_name']); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <p class="survey-description" v-text="survey.description"><?php echo esc_html( $survey->description ) ?></p>
            <div class="loader" v-bind:class="{'is-loading': isLoading}"></div>

            <progress-status inline-template>
                <div class="survey-progress">
                    <span class="survey-progress-done" v-bind:class="{'-done': isCompleted}"></span>
                    <span class="survey-progress-bar" v-bind:style="{width: progressPercentage}"></span>
                </div>
            </progress-status>

            <error-message inline-template>
                <div class="survey-error" v-show="error">
                    <span v-text="error"></span>
                    <span class="survey-error-button" v-on:click.prevent="dismiss()"></span>
                </div>
            </error-message>

            <sections inline-template>
                <div class="sections">

					<?php /**
					 * @var Template $template
					 * @var Survey $survey
					 */

					$welcomeBlocks = $survey->getWelcomeBlocks();
					if ( ! empty( $welcomeBlocks ) ): ?>
                        <section-item uid="welcome" inline-template v-on:next="next()">
                            <transition name="fade">
                                <section class="section welcome" v-show="isVisible">

									<?php foreach ( $welcomeBlocks as $block ): ?>
										<?php echo wp_kses( $block->render(), GetAllowedSurveyTags::invoke() ); ?>
									<?php endforeach; ?>
                                    <a v-on:click.prevent="next()"
                                       class="button -primary"><?php echo esc_html__( 'Start survey', 'totalsurvey' ) ?></a>
                                </section>
                            </transition>
                        </section-item>
					<?php endif; ?>

					<?php foreach ( $survey->sections as $sectionIndex => $section ) : ?>
                        <section-item
                                inline-template
                                uid="<?php echo esc_attr( $section->uid ); ?>"
                                v-bind:index="<?php echo esc_attr( $sectionIndex ); ?>"
                                v-on:submit="validate($event)"
                                v-on:previous="previous($event)"
                                v-on:restart="restart($event)"
                                v-on:cancel-edit="cancelEdit($event)"
                                v-on:input="input($event)">
                            <transition name="fade" mode="in-out" appear>
                                <form class="section"
                                      v-on:submit.prevent="submit($event)"
                                      v-on:input="input($event)"
                                      v-show="isVisible">
                                    <h3 class="section-title"><?php echo esc_html( $section['title'] ); ?></h3>
                                    <p class="section-description"><?php echo nl2br( esc_html( $section['description'] ) ); ?></p>

									<?php foreach ( $section->blocks as $blockIndex => $block ): ?>

										<?php if ( $block->isQuestion() ): ?>
                                            <question inline-template
                                                      uid="<?php echo esc_attr( $block->uid ); ?>"
                                                      v-bind:index="<?php echo esc_attr( $blockIndex ); ?>">
                                                <div class="question">
                                                    <label for="<?php echo esc_attr( $block->uid ) ?>"
                                                           class="question-title"
                                                           v-bind:class="{required: isRequired, '-error': error}">
														<?php echo strip_tags( $block->title, '<br>' ); ?>
                                                    </label>
                                                    <p class="question-description">
														<?php echo esc_html( $block->description ) ?>
                                                    </p>
                                                    <div class="question-field"
													     <?php if ( $block->field->allowOther() ): ?>v-other<?php endif; ?>>
														<?php echo wp_kses( $block->render(), GetAllowedSurveyTags::invoke() ); ?>
                                                        <p class="question-error">{{ error }}</p>
                                                    </div>
                                                </div>
                                            </question>
										<?php else: ?>
											<?php echo wp_kses( $block->render(), GetAllowedSurveyTags::invoke() ); ?>
										<?php endif; ?>
									<?php endforeach; ?>


                                    <div class="section-buttons">
                                        <button tabindex="-2"
                                                type="button" class="button -link section-reset"
                                                v-if="isEditing"
                                                v-on:click.prevent="cancelEdit()">
		                                    <?php esc_html_e( 'Cancel', 'totalsurvey' ); ?>
                                        </button>
                                        <button tabindex="-1"
                                                type="button"
                                                class="button -link section-reset"
                                                v-on:click.prevent="restart()" v-else>
											<?php esc_html_e( 'Start over', 'totalsurvey' ); ?>
                                        </button>


                                        <button v-on:click.prevent="reset()"
                                                tabindex="-1"
                                                type="button"
                                                class="button"
                                                v-bind:disabled="submitInProgress">
											<?php esc_html_e( 'Reset', 'totalsurvey' ); ?>
                                        </button>

                                        <template v-if="index != 0">
                                            <button
                                                type="button"
                                                class="button section-previous"
                                                    v-on:click.prevent="previous()">
												<?php esc_html_e( 'Previous', 'totalsurvey' ); ?>
                                            </button>
                                        </template>

                                        <button class="button -primary section-submit"
                                                type="submit"
                                                v-bind:disabled="submitInProgress">
                                            <template v-if="shouldSubmit">
                                                <span v-if="submitInProgress"><?php esc_html_e( 'Submitting...', 'totalsurvey' ); ?></span>
                                                <span v-else><?php esc_html_e( 'Submit', 'totalsurvey' ); ?></span>
                                            </template>
                                            <template v-else>
												<?php esc_html_e( 'Next', 'totalsurvey' ); ?>
                                            </template>
                                        </button>
                                    </div>
                                </form>
                            </transition>
                        </section-item>
					<?php endforeach; ?>

					<?php /**
					 * @var Template $template
					 * @var Survey $survey
					 */
					$thankYouBlocks = $survey->getThankYouBlocks();
					?>
                    <section-item uid="thankyou" inline-template v-on:reload="reload($event)" v-on:edit="edit($event)">
                        <transition name="fade">
                            <section class="section thankyou" v-show="isVisible">
                                <p v-if="lastEntry.customThankYouMessage" v-html="lastEntry.customThankYouMessage"></p>
                                <template v-else>
									<?php foreach ( $thankYouBlocks as $block ): ?>
										<?php echo wp_kses( $block->render(), GetAllowedSurveyTags::invoke() ); ?>
									<?php endforeach; ?>
                                </template>

                                <div class="section-buttons">
                                    <button type="button" v-on:click="reload()" class="button -primary"
                                            v-if="canRestart">
										<?php echo esc_html__( 'Submit another entry', 'totalsurvey' ) ?>
                                    </button>
                                    <button v-if="canEditEntry"
                                            v-on:click="edit()"
                                            class="button edit">

                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                                             width="24px" fill="#000000">
                                            <path d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                                        </svg>
		                                <?php echo esc_html__( 'Edit Entry', 'totalsurvey' ) ?>
                                    </button>
                                    <div class="alt-actions">
                                        <a v-if="canViewEntry"
                                           v-bind:href="lastEntry.url"
                                           v-print
                                           target="_blank"
                                           class="button print">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                                                 width="24px" fill="#000000">
                                                <path d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
                                            </svg>
											<?php echo esc_html__( 'Print', 'totalsurvey' ) ?>
                                        </a>
                                        <a v-if="canViewEntry"
                                           v-bind:href="lastEntry.url"
                                           target="_blank"
                                           class="button">

                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24"
                                                 width="24px" fill="#000000">
                                                <path d="M0 0h24v24H0z" fill="none"/>
                                                <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                                            </svg>
											<?php echo esc_html__( 'View', 'totalsurvey' ) ?>
                                        </a>
                                    </div>
                                </div>
                            </section>
                        </transition>
                    </section-item>

                </div>
            </sections>

        </div>
    </survey>

	<?php echo esc_html( $after ); ?>
</template>
