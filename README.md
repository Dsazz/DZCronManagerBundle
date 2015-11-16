# DZCronManagerBundle

This bundle provide manager for managing cron table.

Setting up DZCronManagerBundle
===========================

### Step 1 - Requirements and Installing the bundle

The first step is to tell composer that you want to download DZCronManagerBundle which can be achieved by typing the following at the command prompt:

```bash
$ php composer.phar require dz/cron-manager-bundle
```

### Step 2 - Enable the bundle in your kernel

The bundle must be added to your `AppKernel`

```php
# app/AppKernel.php

public function registerBundles()
{
    return array(
        // ...
        new DZ\CronManagerBundle\DZCronManagerBundle(),
        // ...
    );
}
```

Example of using DZCronManagerBundle
===========================

```php
<?php

namespace Acme\CronManagerBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

class CronController extends Controller
{
    /**
     * Displays the current crons.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function listAction(Request $request)
    {
        $cronManager = $this->get('roro_cron.manager');
        $cronManager->parseCronFile();

        $this->addFlash('message', $cronManager->getOutput()); //Gets the output of crontab
        $this->addFlash('error', $cronManager->getError()); //Gets the error of crontab

        $form = $this->get( 'roro_cron.form.cron' );
        return $this->render(/*list of cron page*/, array(
            'crons' => $cronManager->getCrons(), //Gets the collections of crons
            'raw'   => $cronManager->getRaw(),  //Gets a representation of the cron table file
            'form'  => $form->createView(),
        ));
    }

    /**
     * Add a cron to the cron table
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function addAction(Request $request)
    {
        $cronManager = $this->get('roro_cron.manager');
        $cronManager->parseCronFile();

        $cron = $cronManager->create(); //Create cron instance

        $form = $this->get('dz_cron.form.cron');
        $form->setData($cron);

        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $cronManager->add($cron); //Add cron to the cron table collection
                
                //Write the current crons in cron table and save process outputs
                $cronManager->writeCronFileAndSaveProcessOutputs(); 

                $this->addFlash('message', $cronManager->getOutput());
                $this->addFlash('error', $cronManager->getError());

                return $this->redirect($this->generateUrl(/*list of cron page*/));
            }
        }

        return $this->render(/*list of cron page*/, array(
            'crons' => $cronManager->getCrons(),
            'raw'   => $cronManager->getRaw(),
            'form'  => $form->createView(),
        ));
    }

    /**
     * Edit a cron
     *
     * @param integer $id - The line of the cron in the cron table
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse|\Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function editAction($id = null, Request $request)
    {
        $cronManager = $this->get('dz_cron.manager');
        $cronManager->parseCronFile();

        if (null === $id || ! $cron = $cronManager->getCrons()->get($id)) {
            throw new NotFoundHttpException(sprintf('Unable to find the object with id : %s', $id));
        }

        $form = $this->get('dz_cron.form.cron');
        $form->setData($cron);
        if ('POST' == $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $cronManager->getCrons()->set($id, $cron);
                $cronManager->writeCronFileAndSaveProcessOutputs();

                $this->addFlash('message', $cronManager->getOutput());
                $this->addFlash('error', $cronManager->getError());

                return $this->redirect($this->generateUrl(/*list of cron page*/));
            }
        }

        return $this->render(/*cron edit page*/, array(
            'form'  => $form->createView(),
        ));
    }

    /**
     * Wake up a cron from the cron table
     *
     * @param integer $id - The line of the cron in the cron table
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse
     */
    public function wakeupAction($id)
    {
        $cronManager = $this->get('dz_cron.manager');

        $cronManager->parseCronFile();
        $this->addFlash('message', $cronManager->getOutput());
        $this->addFlash('error', $cronManager->getError());

        $cronManager->getCrons()->get($id)->wakeup(); //Wakeup a cron

        $cronManager->writeCronFileAndSaveProcessOutputs();
        $this->addFlash('message', $cronManager->getOutput());
        $this->addFlash('error', $cronManager->getError());

        return $this->redirect($this->generateUrl(/*list of cron page*/));
    }

    /**
     * Suspend a cron from the cron table
     *
     * @param integer $id - The line of the cron in the cron table
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse
     */
    public function suspendAction($id)
    {
        $cronManager = $this->get('dz_cron.manager');

        $cronManager->parseCronFile();
        $this->addFlash('message', $cronManager->getOutput());
        $this->addFlash('error', $cronManager->getError());

        $cronManager->getCrons()->get($id)->suspend(); //Suspend a cron

        $cronManager->writeCronFileAndSaveProcessOutputs();
        $this->addFlash('message', $cronManager->getOutput());
        $this->addFlash('error', $cronManager->getError());

        return $this->redirect($this->generateUrl(/*list of cron page*/));
    }

    /**
     * Remove a cron from the cron table
     *
     * @param $id The line of the cron in the cron table
     *
     * @return \Symfony\Bundle\FrameworkBundle\Controller\RedirectResponse
     */
    public function deleteAction($id, Request $request = null)
    {
        $cronManager = $this->get('roro_cron.manager');

        $cronManager->parseCronFile();
        $this->addFlash('message', $cronManager->getOutput());
        $this->addFlash('error', $cronManager->getError());

        $cronManager->remove($id);

        $cronManager->writeCronFileAndSaveProcessOutputs();
        $this->addFlash('message', $cronManager->getOutput());
        $this->addFlash('error', $cronManager->getError());

        return $this->redirect($this->generateUrl(/*list of cron page*/));
    }

    /**
     * Get log file action
     *
     * @param integer $id   - The line of the cron in the cron table
     * @param string  $type - The type of log file
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logFileAction($id, $type)
    {
        $cronManager = $this->get('roro_cron.manager');
        $cronManager->parseCronFile();

        $cron = $cronManager->getCrons()->get($id);

        $data = array();
        $data['file'] =  ($type == 'log') ? $cron->getLogFile() : $cron->getErrorFile();
        $data['content'] = file_get_contents($data['file']);

        $serializer = new Serializer(array(), array('json' => new JsonEncoder()));

        return new Response($serializer->serialize($data, 'json'));
    }
}
```
