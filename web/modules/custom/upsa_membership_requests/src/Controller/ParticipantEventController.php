<?php

namespace Drupal\upsa_membership_requests\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Controller for the Participant of the event endpoint.
 */
class ParticipantEventController extends ControllerBase {

  public function post_data(Request $request) {
    // Decode the JSON POST data
    $data = json_decode($request->getContent(), TRUE);

    // Validate the data
    if ($this->validate($data)) {
      // Create the node of type 'participant_of_the_event'
      $node = Node::create([
        'type'  => 'participant_of_the_event',
        'title' => $data['name'] . ' ' . $data['surname'],
        'field_name' => $data['name'],
        'field_surname' => $data['surname'],
        'field_email' => $data['email'],
        'field_educational_institution' => $data['educational'],
        'field_event' => ['target_id' => $data['event']],
      ]);

      // Save the node
      $node->save();

      // Return a JSON response
      return new JsonResponse([
        'message' => 'Participant of the event  submitted successfully',
        'nid' => $node->id()
      ]);
    } else {
      // Data validation failed
      throw new HttpException(400, 'Invalid data provided.');
    }
  }

  /**
   * Validate the data.
   */
  private function validate($data) {
    return TRUE;
  }

}
