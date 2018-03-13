<?php

namespace Drupal\search_api_saved_searches\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides routes related to saved searches.
 */
class SavedSearchController extends ControllerBase {

  /**
   * Redirects to the search page for the given saved search.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search_api_saved_search
   *   The saved search.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the search page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown if the search didn't specify a search page path.
   */
  public function viewSearch(SavedSearchInterface $search_api_saved_search) {
    $options = $search_api_saved_search->getOptions();
    if (empty($options['page'])) {
      throw new NotFoundHttpException();
    }
    $url = Url::fromUserInput($options['page'], ['absolute' => TRUE]);
    return new RedirectResponse($url->toString(), 302);
  }

}