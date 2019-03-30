<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Coaches;
use AppBundle\Entity\PlayerProperties\Positions;
use AppBundle\Entity\PlayerProperties\WaterGlasses;
use AppBundle\Entity\Players;
use AppBundle\Entity\PlayersInjured;
use AppBundle\Entity\Teams;
use AppBundle\Entity\YouthTeams;
use AppBundle\Form\PlayersInjuredType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class PlayerController extends Controller
{
    /**
     * @Route("/player", name ="playerView")
     */
    public function IndexView()
    {

        $userId = $this->getUser()->getId();

        $player = $this->getDoctrine()
            ->getRepository(Players::class)
            ->findOneBy(['userId' => $userId]);

        $water_glasses = $this->getDoctrine()
            ->getRepository(WaterGlasses::class)
            ->findBy(['playerId' => $userId], [ 'id' => 'DESC']);

        $currentDate = Date('Y-m-d');

        $time = strtotime($currentDate);
        $today= date('Y-m-d',$time);

        if ($water_glasses == null) {
            $waterTime = 0;
        }else{
            $waterTime = strtotime($water_glasses[0]->getDate());
        }
        $waterDay= date('Y-m-d',$waterTime);

        if ($water_glasses == null || $waterDay < $today ){
            $water = new WaterGlasses();
            $em = $this->getDoctrine()->getManager();
            $water->setPlayerId($this->getUser());
            $water->setDate($currentDate);
            $water->setWaterGlasses(0);
            $em->persist($water);
            $em->flush();
        }
        $water_glasses = $this->getDoctrine()
            ->getRepository(WaterGlasses::class)
            ->findBy(['playerId' => $userId], [ 'id' => 'DESC']);

        $allWater_glasses = $this->getDoctrine()
            ->getRepository(WaterGlasses::class)
            ->findBy(['playerId' => $userId], [ 'id' => 'ASC']);

        if($player->getTeam() != null){
            $team = $player->getTeam();
            $devision = $team->getDevision();
            $teams = $this->getDoctrine()->getRepository(Teams::class)->findBy(['division' => $devision->getId()], ['points' => 'DESC']);
        }else {
            $team = $player->getYouthTeams();
            $devision = $team->getDivision();
            $teams = $this->getDoctrine()->getRepository(YouthTeams::class)->findBy(['division' => $devision->getId()], ['points' => 'DESC']);

        }

        return $this->render('player/index.html.twig', array('coachStatus' => $player->getStatusFromCoaches(),
            'playerFat' => $player->getFat(),
            'pace' => $player->getPace(),
            'waterGlasses' =>  $water_glasses[0]->getWaterGlasses(),
            'allWatersGlasese' => $allWater_glasses,
            'teams' => $teams,
            'myTeam' => $team,
            'profile_img' => $player->getImage()

        ));
    }

    /**
     * @Route("/player/removeWaterGlasses", name = "removeWaterGlassAction")
     */
    public function RemoveWaterGlassesAction()
    {
        $userId = $this->getUser()->getId();

        $water_glasses = $this->getDoctrine()
            ->getRepository(WaterGlasses::class)
            ->findBy(['playerId' => $userId], [ 'id' => 'DESC']);

        if ($water_glasses[0]->getWaterGlasses() > 0){

            $water_glasses[0]->setWaterGlasses($water_glasses->getWaterGlasses() - 1);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($water_glasses[0]);
            $entityManager->flush();

        }
        echo 'success';
        exit;
    }

    /**
     * @Route("/player/addWaterGlasses", name = "addWaterGlassAction")
     */
    public function AddWaterGlassesAction()
    {
        $userId = $this->getUser()->getId();

        $water_glasses = $this->getDoctrine()
            ->getRepository(WaterGlasses::class)
            ->findBy(['playerId' => $userId], [ 'id' => 'DESC']);

        if ($water_glasses[0]->getWaterGlasses() < 21) {

            $water_glasses[0]->setWaterGlasses($water_glasses[0]->getWaterGlasses() + 1);

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($water_glasses[0]);
            $entityManager->flush();

            echo 'success';
            exit;
        }
    }

    /**
     * @Route("/players/settings", name = "player_settings")
     *
     */
    public function SettingsView(\Symfony\Component\HttpFoundation\Request $request){
        $user = $this->getUser()->getPlayer();
        $players = new Players();
        $positions = $this->getDoctrine()->getRepository(Positions::class)->findAll();
        $form = $this->createFormBuilder($players)
            ->add('image', FileType::class, array('data_class' => null, ))
            ->add('height')
            ->add('weight')
            ->add('save', SubmitType::class, ['label' => 'Запаметяване '])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $players->getImage();

            $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();

            try {
                $file->move(
                    $this->getParameter('images_directory'),
                    $fileName
                );
            } catch (FileException $e) {

            }

            $em = $this->getDoctrine()->getManager();
            $user->setImage($fileName);
            $em->persist($user);
            $em->flush();

            return $this->render('player/settings/settings.html.twig',
                array("image" => $user->getImage(),
                'form' => $form->createView(),
                'player' => $user));
        }

        return $this->render('player/settings/settings.html.twig',
            array('form' => $form->createView(),
                "image" => $this->getUser()->getPlayer()->getImage(),
                'profile_img' =>$this->getUser()->getPlayer()->getImage(),
                'player' => $user));
    }

    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }

    /**
     * @Route("/player/training", name="playerTraining")
     */
    public function TrainingView(\Symfony\Component\HttpFoundation\Request $request)
    {
        $player = $this->getUser()->getPlayer();
        $status = new PlayersInjured();
        $form = $this->createForm(PlayersInjuredType::class, $status);
        $form->handleRequest($request);

        $statuses = $this->getDoctrine()->getRepository(PlayersInjured::class)->findBy(['users' =>$player->getId()], ['id' => 'DESC']);

        if ($form->isSubmitted()) {
            $checker1 = true;
            $checker2 = true;
            foreach ($statuses as $stat){
                if ($stat->getStartTreatment() >= $status->getStartTreatment()&& $status->getStartTreatment() <= $stat->getEndTreatment()){
                    $checker1 = false;
                }
                if ($stat->getEndTreatment() >= $status->getEndTreatment()&& $status->getEndTreatment() <= $stat->getEndTreatment()){
                    $checker2 = false;
                }
            }
            if ($checker1 == true && $checker2 == true) {
                $em = $this->getDoctrine()->getManager();
                $status->setUsers($player);
                $em->persist($status);
                $em->flush();
                echo 1;
                exit;
            }else{
                echo 2;
                exit;
            }
        }

        $Current = Date('N');
        $DaysToSunday = 7 - $Current;
        $DaysFromMonday = $Current - 1;
        $Sunday = Date('d-m-Y', StrToTime("+ {$DaysToSunday} Days"));
        $Monday = Date('d-m-Y', StrToTime("- {$DaysFromMonday} Days"));

        if ($player->getYouthTeams() != null){
            $team = $player->getYouthTeams();
        }else{
            $team = $player->getTeam();
        }

        $coaches = $team->getCoaches();
        $schedule = [];

        foreach ($coaches as $coache){
            if($coache->getTeamPosition()->getId() == 1){
                $bigCoache = $coache;
                $schedule = $bigCoache->getSchedule();
            }else{
                $bigCoache = null;
            }
        }

        $bigCoache = null;



        return $this->render('player/training.html.twig' , array('schedule' => $schedule,
            'monday' => strval($Monday),
            'sunday' => strval($Sunday),
            'profile_img' => $player->getImage(),
            'coaches' => $coaches,
            'bigCoache' =>$bigCoache,
            'status' => $statuses
        ));
    }

    /**
     * @Route("/player/deleteStat/{id}", name="playerStatDeleting")
     */
    public function StatRemove($id, \Symfony\Component\HttpFoundation\Request $request)
    {
        $player= $this->getUser()->getPlayer()->getId();

        $stat = $this->getDoctrine()->getRepository(PlayersInjured::class)->find($id);
        if ($stat->getUsers()->getId() != $player){
            return $this->redirectToRoute(playerTraining);

        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($stat);
        $em->flush();
        echo 1;
        exit;
    }



}