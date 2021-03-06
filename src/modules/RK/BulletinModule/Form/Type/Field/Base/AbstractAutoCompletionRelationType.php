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

namespace RK\BulletinModule\Form\Type\Field\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use RK\BulletinModule\Entity\Factory\BulletinFactory;
use RK\BulletinModule\Form\DataTransformer\AutoCompletionRelationTransformer;

/**
 * Auto completion relation field type base class.
 */
abstract class AbstractAutoCompletionRelationType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var BulletinFactory
     */
    protected $entityFactory;

    /**
     * AutoCompletionRelationType constructor.
     *
     * @param Routerinterface $router Router service instance
     * @param BulletinFactory $entityFactory BulletinFactory service instance
     */
    public function __construct(RouterInterface $router, BulletinFactory $entityFactory)
    {
        $this->router = $router;
        $this->entityFactory = $entityFactory;
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new AutoCompletionRelationTransformer($this->entityFactory, $options['object_type'], $options['multiple']);
        $builder->addModelTransformer($transformer);
    }

    /**
     * @inheritDoc
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['object_type'] = $options['object_type'];
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['unique_name_for_js'] = $options['unique_name_for_js'];

        $view->vars['create_url'] = '';
        if (true === $options['allow_editing'] && in_array($options['object_type'], ['notice', 'picture', 'event'])) {
            $view->vars['create_url'] = $this->router->generate('rkbulletinmodule_' . strtolower($options['object_type']) . '_edit');
        }
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'object_type' => 'notice',
                'multiple' => false,
                'unique_name_for_js' => '',
                'allow_editing' => false,
                'attr' => [
                    'class' => 'relation-selector typeahead'
                ]
            ])
            ->setRequired(['object_type', 'unique_name_for_js'])
            ->setAllowedTypes([
                'object_type' => 'string',
                'multiple' => 'bool',
                'unique_name_for_js' => 'string',
                'allow_editing' => 'bool'
            ])
        ;
    }

    /**
     * @inheritDoc
     */
    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\HiddenType';
    }

    /**
     * @inheritDoc
     */
    public function getBlockPrefix()
    {
        return 'rkbulletinmodule_field_autocompletionrelation';
    }
}
