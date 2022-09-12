import { DateTime } from 'luxon'
import {
  BaseModel,
  BelongsTo,
  belongsTo,
  column,
  computed,
  HasMany,
  hasMany,
} from '@ioc:Adonis/Lucid/Orm'
import User from './User'
import { BorderRadiusTypes } from 'App/Helpers/type'
import SignatureEvent from './SignatureEvent'
import { isRelationshipPreloaded } from 'App/Helpers/model'

export default class Signature extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public userId: string

  @column()
  public name: string

  @column()
  public title: string | null

  @column()
  public avatarUrl: string | null

  // TODO make this column enum type
  @column()
  public avatarBorderRadiusType: BorderRadiusTypes = BorderRadiusTypes.SQUARE

  @column()
  public company: string | null

  @column()
  public email: string | null

  @column()
  public website: string | null

  @column()
  public officeAddress: string | null

  @column()
  public officePhoneNumber: string | null

  @column()
  public homePhoneNumber: string | null

  @column()
  public calendarUrl: string | null

  @column()
  public bannerUrl: string | null

  @column()
  public isBannerEnabled: boolean = false

  @column()
  public isDeleted: boolean = false

  @column()
  public isDefault: boolean = false

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @computed({ serializeAs: 'full_name' })
  public get fullName() {
    // TODO send type of model instance instead of string
    isRelationshipPreloaded(this, 'user')

    return this.user.fullName
  }

  @hasMany(() => SignatureEvent)
  public events: HasMany<typeof SignatureEvent>

  public get serializedSignatureBasicInfo() {
    const { id, name, isDefault, createdAt, updatedAt } = this

    return {
      id,
      name,
      isDefault,
      createdAt,
      updatedAt,
    }
  }

  public get serializedSignatureInfo() {
    const {
      id,
      userId,
      fullName,
      name,
      title,
      avatarUrl,
      company,
      email,
      avatarBorderRadiusType,
      website,
      officeAddress,
      officePhoneNumber,
      homePhoneNumber,
      bannerUrl,
      isBannerEnabled,
      isDeleted,
      isDefault,
    } = this

    return {
      id,
      userId,
      fullName,
      name,
      title,
      avatarUrl,
      company,
      email,
      avatarBorderRadiusType,
      website,
      officeAddress,
      officePhoneNumber,
      homePhoneNumber,
      bannerUrl,
      isBannerEnabled,
      isDeleted,
      isDefault,
    }
  }
}
