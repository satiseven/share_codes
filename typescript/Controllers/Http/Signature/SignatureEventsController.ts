import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import CreateSignatureEvent from 'App/Services/Signature/CreateSignatureEvent'
import GetSignatureLinkValidator from 'App/Validators/Signature/GetSignatureLinkValidator'

export default class SignatureEventController {
  /**
   * This has to be a get route because it redirects user to the link from signature
   *
   * The main purpose of this endpoint is redirecting user to the signature link. It's not about the
   * signature events. Creating a signature event is just a `side effect`.
   *
   * We should move this into more semantically correct route naming
   */
  public async get({ request, response }: HttpContextContract) {
    const { linkUrl, emailId } = await request.validate(GetSignatureLinkValidator)

    // TODO: move this service call to a background job, don't block redirection to url
    const service = new CreateSignatureEvent({ emailId })
    await service.call()

    return response.redirect(linkUrl)
  }
}
