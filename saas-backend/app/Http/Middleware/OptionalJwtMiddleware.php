<?php

namespace App\Http\Middleware;


use App\Models\User;
use Closure;
use DateTimeImmutable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\SerializableClosure\Serializers\Signed;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\Clock\Clock;
use Symfony\Component\HttpFoundation\Response;

class OptionalJwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            // No token, proceed anonymously
            return $next($request);
        }

        $parser = new Parser(new JoseEncoder());
        $signingKey = InMemory::plainText(config('app.key'));
        $clock = new Clock(); // Now default
        $validAt = new StrictValidAt($clock);

        $signedWith = new SignedWith(new Sha256(), $signingKey);
        $jwt = new JwtFacade($parser);

        try {
            $parsedToken = $jwt->parse($token, $signedWith, $validAt);

            // Extract jti and uid
            $jti = $parsedToken->claims()->get('jti');
            $uid = $parsedToken->claims()->get('uid');

            $sanctumToken = PersonalAccessToken::findToken($jti);

            if ($sanctumToken && $uid) {
                $user = User::find($uid);

                if ($user) {
                    Auth::login($user);
                    $request->merge(['uid' => $uid]);
                }
            }
        } catch (RequiredConstraintsViolated $e) {
            // Token invalid — skip login, continue anonymously
        } catch (\Throwable $e) {
            // Any other parsing error — skip login, continue anonymously
        }

        return $next($request);
    }
}
