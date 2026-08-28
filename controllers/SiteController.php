<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\services\EquipmentService;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    // Bejelentkezés nélkül csak a bemutatkozó oldal, a belépés és a
                    // hibaoldal érhető el; minden más a belépésre irányít.
                    [
                        'allow' => true,
                        'actions' => ['index', 'login', 'error', 'captcha'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        // A főoldalon a morzsamenü csak a "Főoldal" hivatkozásból áll (lásd a layoutot).
        $service = new EquipmentService();
        $statusFilter = Yii::$app->request->get('status', '');
        $lenderFilter = Yii::$app->request->get('lender_id', '');
        $categoryFilter = Yii::$app->request->get('category_id', '');
        if (Yii::$app->request->isPost) {
            if (Yii::$app->user->isGuest || !Yii::$app->user->identity->canEdit()) {
                throw new \yii\web\ForbiddenHttpException('Ehhez admin jogosultság szükséges.');
            }
            $result = $service->handleAction(Yii::$app->request->post());
            Yii::$app->session->setFlash($result['success'] ? 'success' : 'error', $result['message']);
            return $this->refresh();
        }
        // Kijelentkezve bemutatkozó oldal fogad: a műszerfal kölcsönvevő-adatokat
        // is mutat, azt csak belépés után adjuk ki.
        if (Yii::$app->user->isGuest) {
            return $this->render('landing');
        }

        return $this->render('home', [
            'equipment' => $service->equipment($statusFilter, $lenderFilter, $categoryFilter),
            'loans' => $service->activeLoans(),
            'report' => $service->report(),
            'movements' => $service->recentMovements(),
            'lenders' => $service->lenders(),
            'categories' => $service->categories(),
            'categoryStats' => $service->categoriesWithUsage(),
            'canEdit' => !Yii::$app->user->isGuest && Yii::$app->user->identity->canEdit(),
            'statusFilter' => $statusFilter,
            'lenderFilter' => $lenderFilter,
            'categoryFilter' => $categoryFilter,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
