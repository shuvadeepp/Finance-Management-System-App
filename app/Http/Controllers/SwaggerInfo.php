<?php

/**
 * @OA\Info(
 *     title="CaseStudy API",
 *     version="1.0.0",
 *     description="Finance management API with JWT authentication"
 * )
 *
 * @OA\Server(
 *     url="http://localhost/CaseStudy/api/public/api",
 *     description="Local XAMPP server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
