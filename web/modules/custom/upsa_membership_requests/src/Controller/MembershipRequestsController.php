<?php

namespace Drupal\upsa_membership_requests\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Controller for the Membership Requests endpoint.
 */
class MembershipRequestsController extends ControllerBase {

  public function post_data(Request $request) {
    // Decode the JSON POST data
    $data = json_decode($request->getContent(), TRUE);

    $city_term_id = $this->getOrCreateTerm($data['city'], 'city');
    // Validate the data
    if ($this->validate($data)) {
      // Create the node of type 'membership_requests'
      $node = Node::create([
        'type'  => 'membership_requests',
        'title' => $data['name'] . ' ' . $data['surname'],
        'field_name' => $data['name'],
        'field_surname' => $data['surname'],
        'field_email' => $data['email'],
        'field_city' => ['target_id' => $city_term_id],
        'field_educational_institution' => $data['educational'],
        'field_approved' => '0',
        // Map other fields accordingly
      ]);

      // Save the node
      $node->save();

      // Return a JSON response
      return new JsonResponse([
        'message' => 'Membership request submitted successfully',
        'nid' => $node->id()
      ]);
    } else {
      // Data validation failed
      throw new HttpException(400, 'Invalid data provided.');
    }
  }

  // Function to get or create a taxonomy term and return its ID
  private function getOrCreateTerm($name, $vocabulary) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => $vocabulary]);
    $term = reset($terms);
    if (!$term) {
      $newTerm = Term::create([
        'name' => $name,
        'vid' => $vocabulary,
      ])->save();
      return $newTerm;
    }
    return $term->id();
  }
  /**
   * Validate the data.
   */
  private function validate($data) {
    return TRUE;
  }

}
