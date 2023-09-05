<?php

namespace App\Controller;

use App\Cache\PromotionCache;
use App\DTO\LowestPriceEnquiry;
use App\Entity\Promotion;
use App\Filter\PromotionsFilterInterface;
use App\Repository\ProductRepository;
use App\Service\Serializer\DTOSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private HttpClientInterface $client,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/products/{id}/lowest-price', name: 'lowest_price', methods: 'POST')]
    public function lowestPrice(
        Request $request,
        int $id,
        DTOSerializer $serializer,
        PromotionsFilterInterface $promotionsFilter,
        PromotionCache $promotionCache
    ): JsonResponse {

        /** @var LowestPriceEnquiry $lowestPriceEnquiry */
        $lowestPriceEnquiry = $serializer->deserialize($request->getContent(), LowestPriceEnquiry::class, 'json');

        $product = $this->productRepository->findOrFail($id);

        $lowestPriceEnquiry->setProduct($product);

        $promotions = $promotionCache->findValidForProduct($product, $lowestPriceEnquiry->getRequestDate());

        $modifiedEnquiry = $promotionsFilter->apply($lowestPriceEnquiry, ...$promotions);

        $responseContent = $serializer->serialize($modifiedEnquiry, 'json');

//        return new Response($responseContent, Response::HTTP_OK, ['Content-Type' => 'application/json']);

        return new JsonResponse($responseContent, Response::HTTP_OK, json: true);
//        return $this->json($lowestPriceEnquiry);
    }

    #[Route('/products/{id}/promotions', name: 'promotions', methods: 'GET')]
    public function promotions(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ProductController.php',
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/products/{id}', name: 'show_product', methods: 'GET')]
    public function show(int $id, Request $request): Response
    {
        $params = $request->query->all();

        $product = $this->productRepository->find($id);

        $response = $this->client->request(
            'POST',
            $this->generateUrl('lowest_price', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            [
                'json' => [
                    'quantity' => $params['quantity'] ?? 1,
                    'request_location' => $params['requestLocation'] ?? '',
                    'voucher_code' => $params['voucherCode'] ?? '',
                    'request_date' => date('Y-m-d'),
                    'product_id' => $product->getId(),
                ],
            ]
        );

        if ($response->getStatusCode() == Response::HTTP_OK) {
            $promotionData = $response->toArray();

            return $this->render('product/show.html.twig', [
                'product' => $product,
                'promotion' => $promotionData,
            ]);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'promotion' => null,
        ]);

    }
}
