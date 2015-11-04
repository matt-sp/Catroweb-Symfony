<?php
namespace Catrobat\AppBundle\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Catrobat\AppBundle\Entity\Program;
use Symfony\Component\HttpFoundation\JsonResponse;
use Catrobat\AppBundle\Entity\GameJam;
use Catrobat\AppBundle\Exceptions\InvalidCatrobatFileException;
use Catrobat\AppBundle\Exceptions\Upload\NoGameJamException;
use Catrobat\AppBundle\Responses\ProgramListResponse;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameSubmissionController extends Controller
{

    /**
     * @Route("/api/gamejam/finalize/{id}", name="gamejam_form_submission")
     * @Method({"GET"})
     */
    public function formSubmittedAction(Request $request, Program $program)
    {
        if ($program->getGamejam() != null) {
            if (! $program->isAcceptedForGameJam()) {
                $program->setAcceptedForGameJam(true);
                $this->getDoctrine()
                    ->getManager()
                    ->persist($program);
                $this->getDoctrine()
                    ->getManager()
                    ->flush();
            }
            return JsonResponse::create(array(
                "statusCode" => "200",
                "message" => "Program accepted for this gamejam"
            ));
        } else {
            return JsonResponse::create(array(
                "statusCode" => "999",
                "message" => "This program was not submitted to a gamejam"
            ));
        }
    }

    /**
     * @Route("/api/gamejam/sampleprograms.json", name="api_gamejam_sample_programs")
     * @Method({"GET"})
     */
    public function getSampleProgramsForCurrentGamejam()
    {
        $gamejam = $this->get("gamejamrepository")->getCurrentGameJam();
        if ($gamejam == null) {
            throw new NoGameJamException();
        }
        return new ProgramListResponse($gamejam->getSamplePrograms(), count($gamejam->getSamplePrograms()));
    }

    /**
     * @Route("/api/gamejam/submissions.json", name="api_gamejam_submissions")
     * @Method({"GET"})
     */
    public function getSubmissionsForCurrentGamejam(Request $request)
    {
        $limit = intval($request->query->get('limit', 20));
        $offset = intval($request->query->get('offset', 0));
        
        $gamejam = $this->get("gamejamrepository")->getCurrentGameJam();
        if ($gamejam == null) {
            throw new NoGameJamException();
        }
        $criteria_count = Criteria::create()->where(Criteria::expr()->eq("gamejam_submission_accepted", true));
        $criteria = Criteria::create()->where(Criteria::expr()->eq("gamejam_submission_accepted", true))
            ->orderBy(array("gamejam_submission_date" => Criteria::DESC))
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        return new ProgramListResponse($gamejam->getPrograms()->matching($criteria), $gamejam->getPrograms()
            ->matching($criteria_count)
            ->count());
    }

    /**
     * @Route("/gamejam/submit/{id}", name="gamejam_web_submit")
     * @Method({"GET"})
     */
    public function webSubmitAction(Request $request, Program $program)
    {
        $gamejam = $this->get("gamejamrepository")->getCurrentGameJam();
        if ($gamejam == null) {
            throw new \Exception("No Game Jam!");
        }
        if ($program->getGamejam() != null && $program->getGamejam() != $gamejam) {
            throw new \Exception("Game was alraedy submitted to another gamejam!");
        }
        if ($program->isAcceptedForGameJam()) {
            return new RedirectResponse($this->generateUrl("program", array(
                "id" => $program->getId()
            )));
        }
        $program->setGamejam($gamejam);
        $program->setGameJamSubmissionDate(new \DateTime());
        $this->getDoctrine()
            ->getManager()
            ->persist($program);
        $this->getDoctrine()
            ->getManager()
            ->flush();
        
        $url = $this->assembleFormUrl($gamejam, $program->getUser(), $program);
        
        if ($url != null) {
            return new RedirectResponse($url);
        } else {
            return new RedirectResponse($this->generateUrl("program", array(
                "id" => $program->getId()
            )));
        }
    }
    
    private function assembleFormUrl($gamejam, $user, $program)
    {
        $url = $gamejam->getFormUrl();
        $url = str_replace("%CAT_ID%", $program->getId(), $url);
        $url = str_replace("%CAT_MAIL%", $user->getEmail(), $url);
        $url = str_replace("%CAT_NAME%", $user->getUsername(), $url);
        return $url;
    }
}