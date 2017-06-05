<?php
/**
 * @file Contains \Drupal\efq\Form\FilterForm
 */

namespace Drupal\efq\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

 
/**
 * A Class for creating a form to use to filter efq queries
 *
 */
class FilterForm extends FormBase {


    /**
     * {@inheritdoc}.
     */
    public function getFormId() {
        return 'filterForm';
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

    }

}
