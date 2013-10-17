<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Xmlps\Logger\Logger;
use Zend\Mvc\I18n\Translator;
use User\Model\DAO\UserDAO;
use Admin\Form\UserRemovalForm;

class UserManagementController extends AbstractActionController {
    protected $logger;
    protected $translator;

    protected $userDAO;
    protected $userRemovalForm;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param Translator $translator
     * @param UserDAO $userDAO
     * @param UserRemovalFrom $userRemovalForm
     * @return void
     */
    public function __construct(
        Logger $logger,
        Translator $translator,
        UserDAO $userDAO,
        UserRemovalForm $userRemovalForm
    )
    {
        $this->logger = $logger;
        $this->translator = $translator;
        $this->userDAO = $userDAO;
        $this->userRemovalForm = $userRemovalForm;
    }

    /**
     * List users
     *
     * @return mixed Array containing view variables
     */
    public function listAction()
    {
        // Get the paginator
        $paginator = $this->userDAO->getUserPaginator();
        $page = $this->params()->fromRoute('page');
        $paginator ->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(20);

        // Display error if we got no messages
        if (empty($paginator)) {
            $this->layout()->messages = array(
                'info' => array( $this->translator->translate(
                    'admin.user.noEntriesFound'
                )),
            );
            return;
        }

        return array('users' => $paginator);
    }

    /**
     * Edit a user
     *
     * @return void
     */
    public function editAction()
    {

    }

    /**
     * Remove a user
     *
     * @return void
     */
    public function removeAction()
    {
        // Process user removal requests
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $userId = (int) $data['userId'];

            // Fetch the user object
            $user = $this->userDAO->find($userId);

            if (!$user) {
                throw new \Exception('Couldn\'t find user with id ' . $userId);
            }

            // Remove the user
            $this->userDAO->remove($user);

            $flashMessenger = $this->flashMessenger();
            $flashMessenger->setNamespace('success');
            $flashMessenger->addMessage(
                $this->translator->translate(
                    sprintf(
                        $this->translator->translate(
                            'admin.userManagement.userRemovalSuccess'
                        ),
                        $user->email
                    )
                )
            );

            $this->logger->info(
                sprintf(
                    $this->translator->translate(
                        'admin.userManagement.userRemovalSuccessLog'
                    ),
                    $user->email
                )
            );

            return $this->redirect()->toRoute(
                'admin/user-management',
                array('action' => 'list')
            );
        }

        $userId = $this->params()->fromRoute('userId');
        $user = $this->userDAO->find($userId);

        if (!$user) {
            $this->getResponse()->setStatusCode(404);
            return;
        }

        return array(
            'userRemovalForm' => $this->userRemovalForm,
            'user' => $user,
        );
    }
}
