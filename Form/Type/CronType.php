<?php
/**
 * This file is part of the DZCronManagerBundle.
 *
 * (c) Stanislav Stepanenko <dsazztazz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DZ\CronManagerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * CronType
 *
 * @author Stanislav Stepanenko <dsazztazz@gmail.com>
 */
class CronType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('minute');
        $builder->add('hour');
        $builder->add('dayOfMonth');
        $builder->add('month');
        $builder->add('dayOfWeek');
        $builder->add('command');
        $builder->add('logFile', 'text', array(
            'required' => false,
        ));
        $builder->add('errorFile', 'text', array(
            'required' => false,
        ));
        $builder->add('comment', 'text', array(
            'required' => false,
        ));
        $builder->addEventListener(
            FormEvents::SUBMIT,
            array($this, 'onPreSetData')
        );
    }

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();

        $this->isValidFieldFilePath(
            $form->get('logFile'), $form->get('logFile')->getData()
        );

        $this->isValidFieldFilePath(
            $form->get('errorFile'), $form->get('errorFile')->getData()
        );
    }

    /**
     * Check field for file path is valid
     *
     * @param FormInterface $field    - the field of form
     * @param string        $filePath - the file path data field
     *
     * @return boolean
     */
    protected function isValidFieldFilePath(FormInterface $field, $filePath)
    {
        if ($filePath) {
            $file = new \SplFileInfo($filePath);

            if (file_exists($filePath)
                && !$file->isFile()
                && !is_writable(dirname($filePath))
            ) {
                $field->addError(
                    new FormError("Wrong file path !")
                );

                return false;
            } elseif (!is_writable(dirname($filePath))) {
                $field->addError(
                    new FormError("Wrong file path !")
                );

                return false;
            }
        }

        return true;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'DZ\CronManagerBundle\Entity\Cron'
        ));
    }
    
    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'cron';
    }
}
