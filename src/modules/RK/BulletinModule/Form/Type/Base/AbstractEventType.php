<?php
/**
 * Bulletin.
 *
 * @copyright Ralf Koester (RK)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Ralf Koester <ralf@familie-koester.de>.
 * @link http://k62.de
 * @link http://zikula.org
 * @version Generated by ModuleStudio (http://modulestudio.de).
 */

namespace RK\BulletinModule\Form\Type\Base;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Common\Translator\TranslatorTrait;
use Zikula\ExtensionsModule\Api\VariableApi;
use RK\BulletinModule\Entity\Factory\BulletinFactory;
use RK\BulletinModule\Helper\FeatureActivationHelper;
use RK\BulletinModule\Helper\ListEntriesHelper;
use RK\BulletinModule\Helper\TranslatableHelper;

/**
 * Event editing form type base class.
 */
abstract class AbstractEventType extends AbstractType
{
    use TranslatorTrait;

    /**
     * @var BulletinFactory
     */
    protected $entityFactory;

    /**
     * @var VariableApi
     */
    protected $variableApi;

    /**
     * @var TranslatableHelper
     */
    protected $translatableHelper;

    /**
     * @var ListEntriesHelper
     */
    protected $listHelper;

    /**
     * @var FeatureActivationHelper
     */
    protected $featureActivationHelper;

    /**
     * EventType constructor.
     *
     * @param TranslatorInterface $translator     Translator service instance
     * @param BulletinFactory        $entityFactory Entity factory service instance
     * @param VariableApi         $variableApi VariableApi service instance
     * @param TranslatableHelper  $translatableHelper TranslatableHelper service instance
     * @param ListEntriesHelper   $listHelper     ListEntriesHelper service instance
     * @param FeatureActivationHelper $featureActivationHelper FeatureActivationHelper service instance
     */
    public function __construct(
        TranslatorInterface $translator,
        BulletinFactory $entityFactory,
        VariableApi $variableApi,
        TranslatableHelper $translatableHelper,
        ListEntriesHelper $listHelper,
        FeatureActivationHelper $featureActivationHelper
    ) {
        $this->setTranslator($translator);
        $this->entityFactory = $entityFactory;
        $this->variableApi = $variableApi;
        $this->translatableHelper = $translatableHelper;
        $this->listHelper = $listHelper;
        $this->featureActivationHelper = $featureActivationHelper;
    }

    /**
     * Sets the translator.
     *
     * @param TranslatorInterface $translator Translator service instance
     */
    public function setTranslator(/*TranslatorInterface */$translator)
    {
        $this->translator = $translator;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addEntityFields($builder, $options);
        if ($this->featureActivationHelper->isEnabled(FeatureActivationHelper::CATEGORIES, 'event')) {
            $this->addCategoriesField($builder, $options);
        }
        $this->addIncomingRelationshipFields($builder, $options);
        $this->addModerationFields($builder, $options);
        $this->addReturnControlField($builder, $options);
        $this->addSubmitButtons($builder, $options);
    }

    /**
     * Adds basic entity fields.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function addEntityFields(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add('description', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', [
            'label' => $this->__('Description') . ':',
            'empty_data' => '',
            'attr' => [
                'maxlength' => 5000,
                'class' => '',
                'title' => $this->__('Enter the description of the event')
            ],
            'required' => false,
        ]);
        
        if ($this->variableApi->getSystemVar('multilingual') && $this->featureActivationHelper->isEnabled(FeatureActivationHelper::TRANSLATIONS, 'event')) {
            $supportedLanguages = $this->translatableHelper->getSupportedLanguages('event');
            if (is_array($supportedLanguages) && count($supportedLanguages) > 1) {
                $currentLanguage = $this->translatableHelper->getCurrentLanguage();
                $translatableFields = $this->translatableHelper->getTranslatableFields('event');
                $mandatoryFields = $this->translatableHelper->getMandatoryFields('event');
                foreach ($supportedLanguages as $language) {
                    if ($language == $currentLanguage) {
                        continue;
                    }
                    $builder->add('translations' . $language, 'RK\BulletinModule\Form\Type\Field\TranslationType', [
                        'fields' => $translatableFields,
                        'mandatory_fields' => $mandatoryFields[$language],
                        'values' => isset($options['translations'][$language]) ? $options['translations'][$language] : []
                    ]);
                }
            }
        }
        
        $builder->add('eventTitle', 'Symfony\Component\Form\Extension\Core\Type\TextType', [
            'label' => $this->__('Event title') . ':',
            'empty_data' => '',
            'attr' => [
                'maxlength' => 255,
                'class' => '',
                'title' => $this->__('Enter the event title of the event')
            ],
            'required' => true,
        ]);
        
        $builder->add('startDate', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType', [
            'label' => $this->__('Start date') . ':',
            'label_attr' => [
                'class' => 'tooltips',
                'title' => $this->__('startpoint of the event')
            ],
            'help' => $this->__('startpoint of the event'),
            'empty_data' => '',
            'attr' => [
                'class' => ' validate-daterange-event',
                'title' => $this->__('Enter the start date of the event')
            ],
            'required' => true,
            'empty_data' => date('Y-m-d H:i:s'),
            'with_seconds' => true,
            'date_widget' => 'single_text',
            'time_widget' => 'single_text'
        ]);
        
        $builder->add('endDate', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType', [
            'label' => $this->__('End date') . ':',
            'empty_data' => '',
            'attr' => [
                'class' => ' validate-daterange-event',
                'title' => $this->__('Enter the end date of the event')
            ],
            'required' => true,
            'empty_data' => date('Y-m-d H:i:s'),
            'with_seconds' => true,
            'date_widget' => 'single_text',
            'time_widget' => 'single_text'
        ]);
    }

    /**
     * Adds a categories field.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function addCategoriesField(FormBuilderInterface $builder, array $options)
    {
        $builder->add('categories', 'Zikula\CategoriesModule\Form\Type\CategoriesType', [
            'label' => $this->__('Category') . ':',
            'empty_data' => null,
            'attr' => [
                'class' => 'category-selector'
            ],
            'required' => false,
            'multiple' => false,
            'module' => 'RKBulletinModule',
            'entity' => 'EventEntity',
            'entityCategoryClass' => 'RK\BulletinModule\Entity\EventCategoryEntity'
        ]);
    }

    /**
     * Adds fields for incoming relationships.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function addIncomingRelationshipFields(FormBuilderInterface $builder, array $options)
    {
        $queryBuilder = function(EntityRepository $er) {
            // select without joins
            return $er->getListQueryBuilder('', '', false);
        };
        if (true === $options['filter_by_ownership']) {
            $queryBuilder = function(EntityRepository $er) {
                // select without joins
                $qb = $er->getListQueryBuilder('', '', false);
                $qb = $er->addCreatorFilter($qb);
        
                return $qb;
            };
        }
        $builder->add('notice', 'RK\BulletinModule\Form\Type\Field\AutoCompletionRelationType', [
            'object_type' => 'notice',
            'multiple' => false,
            'unique_name_for_js' => 'bullEvent_Notice',
            'allow_editing' => true,
            'label' => $this->__('Notice'),
            'attr' => [
                'title' => $this->__('Choose the notice')
            ]
        ]);
    }

    /**
     * Adds special fields for moderators.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function addModerationFields(FormBuilderInterface $builder, array $options)
    {
        if (!$options['has_moderate_permission']) {
            return;
        }
    
        $builder->add('moderationSpecificCreator', 'RK\BulletinModule\Form\Type\Field\UserType', [
            'mapped' => false,
            'label' => $this->__('Creator') . ':',
            'attr' => [
                'maxlength' => 11,
                'class' => ' validate-digits',
                'title' => $this->__('Here you can choose a user which will be set as creator')
            ],
            'empty_data' => 0,
            'required' => false,
            'help' => $this->__('Here you can choose a user which will be set as creator')
        ]);
        $builder->add('moderationSpecificCreationDate', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType', [
            'mapped' => false,
            'label' => $this->__('Creation date') . ':',
            'attr' => [
                'class' => '',
                'title' => $this->__('Here you can choose a custom creation date')
            ],
            'empty_data' => '',
            'required' => false,
            'with_seconds' => true,
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'help' => $this->__('Here you can choose a custom creation date')
        ]);
    }

    /**
     * Adds the return control field.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function addReturnControlField(FormBuilderInterface $builder, array $options)
    {
        if ($options['mode'] != 'create') {
            return;
        }
        $builder->add('repeatCreation', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', [
            'mapped' => false,
            'label' => $this->__('Create another item after save'),
            'required' => false
        ]);
    }

    /**
     * Adds submit buttons.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function addSubmitButtons(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['actions'] as $action) {
            $builder->add($action['id'], 'Symfony\Component\Form\Extension\Core\Type\SubmitType', [
                'label' => $this->__(/** @Ignore */$action['title']),
                'icon' => ($action['id'] == 'delete' ? 'fa-trash-o' : ''),
                'attr' => [
                    'class' => $action['buttonClass'],
                    'title' => $this->__(/** @Ignore */$action['description'])
                ]
            ]);
        }
        $builder->add('reset', 'Symfony\Component\Form\Extension\Core\Type\ResetType', [
            'label' => $this->__('Reset'),
            'icon' => 'fa-refresh',
            'attr' => [
                'class' => 'btn btn-default',
                'formnovalidate' => 'formnovalidate'
            ]
        ]);
        $builder->add('cancel', 'Symfony\Component\Form\Extension\Core\Type\SubmitType', [
            'label' => $this->__('Cancel'),
            'icon' => 'fa-times',
            'attr' => [
                'class' => 'btn btn-default',
                'formnovalidate' => 'formnovalidate'
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'rkbulletinmodule_event';
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                // define class for underlying data (required for embedding forms)
                'data_class' => 'RK\BulletinModule\Entity\EventEntity',
                'empty_data' => function (FormInterface $form) {
                    return $this->entityFactory->createEvent();
                },
                'error_mapping' => [
                    'isStartDateBeforeEndDate' => 'startDate',
                ],
                'mode' => 'create',
                'actions' => [],
                'has_moderate_permission' => false,
                'translations' => [],
                'filter_by_ownership' => true,
                'inline_usage' => false
            ])
            ->setRequired(['mode', 'actions'])
            ->setAllowedTypes([
                'mode' => 'string',
                'actions' => 'array',
                'has_moderate_permission' => 'bool',
                'translations' => 'array',
                'filter_by_ownership' => 'bool',
                'inline_usage' => 'bool'
            ])
            ->setAllowedValues([
                'mode' => ['create', 'edit']
            ])
        ;
    }
}
